<?php

use App\Actions\Component\HasComponentRelease;
use App\Actions\Component\RestartComponentWorkers;
use App\Actions\EnvironmentDeployment\AdvanceEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Actions\EnvironmentDeployment\FinishEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\RollbackComponentRelease;
use App\Actions\EnvironmentDeployment\RunComponentStage;
use App\Actions\EnvironmentDeployment\StartEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\UpdateEnvironmentDeploymentScript;
use App\Actions\EnvironmentDeployment\UpdateEnvironmentDeploymentSettings;
use App\Enums\ComponentStatus;
use App\Enums\DeploymentStage;
use App\Enums\DeploymentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Enums\WorkerStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Jobs\EnvironmentDeployment\AdvanceEnvironmentDeploymentJob;
use App\Jobs\EnvironmentDeployment\DeployComponentStageJob;
use App\Jobs\EnvironmentDeployment\StartEnvironmentDeploymentJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\SourceControl;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake('');
    Http::preventStrayRequests();
    Http::fake(['https://api.github.com/repos/*/commits/*' => Http::response(['sha' => str_repeat('a', 40), 'commit' => ['message' => 'Pinned']])]);
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $source = SourceControl::factory()->github()->create(['project_id' => $this->server->project_id]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'custom', 'source_control_id' => $source->id]);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    $this->environment->primary_component_id = $this->component->id;
    $this->environment->save();
});

test('github app clones receive the installation token and the pinned SHA without a token in logs', function () {
    $source = SourceControl::factory()->githubApp()->create(['project_id' => $this->server->project_id]);
    Cache::put('github_app_token_'.$source->external_identifier, 'installation-secret', 3600);
    $this->application->update(['source_control_id' => $source->id]);
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    $this->mock(\App\SSH\OS\Git::class)->shouldReceive('cloneRepository')->once()->withArgs(function (\App\DTOs\GitCloneDTO $target) use ($run): bool {
        return $target->usesToken && $target->variables === ['GIT_HTTP_TOKEN' => 'installation-secret']
            && $target->commit === $run->fresh()->commit_id && $target->log->id === $run->deployments()->sole()->log_id;
    });
    $deployment = $run->deployments()->sole();
    app(RunComponentStage::class)->run($deployment->id, DeploymentStage::CLONE);
    expect($deployment->log->getContent())->not->toContain('installation-secret');
    SSH::assertNotExecutedContains('installation-secret');
});

test('scripts select component overrides and load staged or live env with pinned metadata', function (DeploymentStage $stage) {
    $this->environment->deploymentScripts()->create(['name' => $stage->value, 'content' => 'echo inherited']);
    $this->component->deploymentScripts()->create(['name' => $stage->value, 'content' => 'echo component']);
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    $run->refresh();
    app(AdvanceEnvironmentDeployment::class)->dispatch($run, $stage);
    $deployment = $run->deployments()->sole();
    app(RunComponentStage::class)->run($deployment->id, $stage);
    $commands = implode("\n", SSH::getExecutedCommands());
    preg_match('/echo ([A-Za-z0-9+\/=]+) \| base64 -d/', $commands, $matches);
    $script = base64_decode($matches[1]);
    $inner = "set -e\nset -o pipefail\nexport COMMIT_ID='".$run->commit_id."'\nexport RELEASE_PATH='".$deployment->path()."'\nexport BRANCH='".$run->branch."'\necho component";
    expect($script)->toContain(escapeshellarg($inner), escapeshellarg($this->component->envPath().($stage === DeploymentStage::POST_RELEASE ? '' : '.next')))->not->toContain('echo inherited');
    expect($deployment->fresh()->status)->toBe(DeploymentStatus::FINISHED);
})->with([DeploymentStage::BUILD, DeploymentStage::PRE_RELEASE, DeploymentStage::POST_RELEASE]);

test('environment scripts are inherited and empty component scripts intentionally skip a stage', function () {
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    app(AdvanceEnvironmentDeployment::class)->dispatch($run->fresh(), DeploymentStage::BUILD);
    $this->environment->deploymentScripts()->create(['name' => 'build', 'content' => 'echo inherited']);
    $deployment = $run->deployments()->sole();
    app(RunComponentStage::class)->run($deployment->id, DeploymentStage::BUILD);
    expect(implode('', SSH::getExecutedCommands()))->toContain('base64 -d');
    SSH::fake('');
    $this->component->deploymentScripts()->create(['name' => 'build', 'content' => '']);
    app(AdvanceEnvironmentDeployment::class)->dispatch($run->fresh(), DeploymentStage::BUILD);
    app(RunComponentStage::class)->run($deployment->id, DeploymentStage::BUILD);
    SSH::assertNotExecutedContains('');
});

test('stage jobs use a component lock and advance only after releasing it', function () {
    $run = app(DeployEnvironment::class)->run($this->environment);
    (new StartEnvironmentDeploymentJob($run->id))->handle();
    $deployment = $run->deployments()->sole();
    $job = (new DeployComponentStageJob($deployment->id, $this->component->id, $run->id, DeploymentStage::CLONE))->withFakeQueueInteractions();
    $lock = Cache::lock('unique-queue:component-'.$this->component->id, 60);
    $lock->get();
    try {
        $job->handle();
        $job->assertReleased(30);
        expect($run->fresh()->current_stage)->toBe(DeploymentStage::CLONE);
        SSH::assertNotExecutedContains('');
    } finally {
        $lock->release();
    }
    $this->partialMock(AdvanceEnvironmentDeployment::class)->shouldReceive('advance')->once()->with($run->id, DeploymentStage::CLONE, false)->andReturnUsing(function () use ($lock): void {
        expect($lock->get())->toBeTrue();
        $lock->release();
    });
    $job->handle();
    expect($job->timeout)->toBe(1200)->and($job->failOnTimeout)->toBeTrue()
        ->and((new ReflectionMethod($job, 'lockSeconds'))->invoke($job))->toBe(1260);
});

test('stage failures defer barrier advancement until the component mutex is free', function () {
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    $deployment = $run->deployments()->sole();
    $job = new DeployComponentStageJob($deployment->id, $this->component->id, $run->id, DeploymentStage::CLONE);
    $job->failed(new Exception('Do not leak sensitive error data'));
    Queue::assertPushed(AdvanceEnvironmentDeploymentJob::class);
    $advance = (new AdvanceEnvironmentDeploymentJob($run->id, $this->component->id, DeploymentStage::CLONE, false))->withFakeQueueInteractions();
    $lock = Cache::lock('unique-queue:component-'.$this->component->id, 60);
    $lock->get();
    try {
        $advance->handle();
        $advance->assertReleased(30);
        expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING);
    } finally {
        $lock->release();
    }
    $advance->handle();
    expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::FAILED)
        ->and($deployment->log->getContent())->not->toContain('Do not leak sensitive error data');
    SSH::fake('');
    $job->handle();
    SSH::assertNotExecutedContains('');
});

test('start failures become terminal without participant side effects and duplicate starts are harmless', function () {
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake(['https://api.github.com/repos/*/commits/*' => Http::response(['sha' => 'not-a-sha'])]);
    $run = app(DeployEnvironment::class)->run($this->environment);
    expect(fn () => app(StartEnvironmentDeployment::class)->start($run->id))->toThrow(RuntimeException::class);
    $locks = [];
    foreach ([Application::class, Environment::class, EnvironmentDeployment::class] as $model) {
        $model::addGlobalScope('verify-start-failure-locks', function (\Illuminate\Database\Eloquent\Builder $query) use (&$locks, $model): void {
            if ($query->getQuery()->lock === true) {
                $locks[] = $model;
            }
        });
    }
    (new StartEnvironmentDeploymentJob($run->id))->failed(new Exception('Private provider response'));
    expect($locks)->toBe([Application::class, Environment::class, EnvironmentDeployment::class]);
    expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::FAILED);
    app(StartEnvironmentDeployment::class)->start($run->id);
    app(StartEnvironmentDeployment::class)->start(999999);
    app(StartEnvironmentDeployment::class)->failed($run->id);
    app(StartEnvironmentDeployment::class)->failed(999999);
    expect($run->deployments()->count())->toBe(0);
    SSH::assertNotExecutedContains('');
});

test('rollback restores a previous active deployment and restarts its processes', function () {
    $previousRun = EnvironmentDeployment::factory()->for($this->environment)->create(['status' => EnvironmentDeploymentStatus::FINISHED]);
    $previous = Deployment::factory()->for($this->component)->create(['site_id' => null, 'release' => 'previous', 'active' => true, 'environment_deployment_id' => $previousRun->id]);
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    $deployment = $run->deployments()->sole();
    $deployment->release_started = true;
    $deployment->save();
    $deployment->activate();
    $this->mock(RestartComponentWorkers::class)->shouldReceive('restart')->once()->withArgs(fn (Component $component): bool => $component->id === $this->component->id);
    app(RollbackComponentRelease::class)->rollback($deployment);
    $this->assertDatabaseHas('deployments', ['id' => $previous->id, 'active' => true]);
    $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'active' => false]);
    SSH::assertExecutedContains('ln -sfn -- '.escapeshellarg($previous->path()));
    SSH::assertExecutedContains('mv -f -- "$file.restore" "$file"');
});

test('first release rollback removes current and stops processes while untouched participants do nothing', function () {
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    $deployment = $run->deployments()->sole();
    app(RollbackComponentRelease::class)->rollback($deployment);
    SSH::assertNotExecutedContains('');
    $worker = Worker::factory()->for($this->component)->for($this->server)->create(['site_id' => null, 'status' => WorkerStatus::RUNNING]);
    $deployment->release_started = true;
    $deployment->save();
    $deployment->activate();
    app(RollbackComponentRelease::class)->rollback($deployment);
    $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'active' => false]);
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'status' => WorkerStatus::STOPPED]);
    SSH::assertExecutedContains('rm -f -- '.escapeshellarg($this->component->currentPath()));
    SSH::assertExecutedContains('supervisorctl stop');
});

test('node releases execute suggested build and start the proxy process once even with worker capability', function (bool $workerCapability) {
    $this->application->update(['runtime' => 'node']);
    foreach ($this->application->preset()->defaultScripts() as $stage => $content) {
        $this->environment->deploymentScripts()->create(['name' => $stage, 'content' => $content]);
    }
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web', 'config' => ['port' => 3200, 'start_command' => 'npm start']]);
    if ($workerCapability) {
        ComponentCapability::factory()->for($this->component)->create(['capability' => 'worker']);
    }
    $worker = Worker::factory()->for($this->component)->for($this->server)->create(['site_id' => null, 'status' => WorkerStatus::STOPPED]);
    $this->mock(HasComponentRelease::class)->shouldReceive('check')->andReturn(true);
    SSH::fake("{$worker->id}:{$worker->id}_00: started");
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    $run->refresh();
    while (! $run->status->isTerminal()) {
        $stage = $run->current_stage;
        foreach (app(AdvanceEnvironmentDeployment::class)->participants($run, $stage)->get() as $deployment) {
            app(RunComponentStage::class)->run($deployment->id, $stage);
        }
        app(AdvanceEnvironmentDeployment::class)->advance($run->id, $stage);
        $run->refresh();
    }
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'status' => WorkerStatus::RUNNING]);
    expect($run->status)->toBe(EnvironmentDeploymentStatus::FINISHED);
    expect(collect(SSH::getExecutedCommands())->filter(fn (string $command): bool => str_contains($command, 'supervisorctl restart')))->toHaveCount(1);
})->with([false, true]);

test('a failed process restart fails the release instead of silently succeeding', function () {
    $worker = Worker::factory()->for($this->component)->for($this->server)->create(['site_id' => null, 'status' => WorkerStatus::STOPPED]);
    $this->mock(HasComponentRelease::class)->shouldReceive('check')->andReturn(true);
    SSH::fake("{$worker->id}:{$worker->id}_00: ERROR (spawn error)");
    expect(fn () => app(RestartComponentWorkers::class)->restart($this->component))->toThrow(RuntimeException::class);
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'status' => WorkerStatus::FAILED]);
});

test('pruning retains the newest N releases plus active and does not run under a stage lock', function () {
    $this->environment->keep_releases = 2;
    $this->environment->save();
    $run = EnvironmentDeployment::factory()->for($this->environment)->create(['status' => EnvironmentDeploymentStatus::FINISHED]);
    $deployments = collect(range(1, 5))->map(fn (int $id): Deployment => Deployment::factory()->for($this->component)->create([
        'site_id' => null, 'environment_deployment_id' => EnvironmentDeployment::factory()->for($this->environment)->create(['status' => EnvironmentDeploymentStatus::FINISHED])->id,
        'release' => 'release-'.$id, 'active' => $id === 1,
    ]));
    $last = $deployments->last();
    $last->environment_deployment_id = $run->id;
    $last->save();
    $lock = Cache::lock('unique-queue:component-'.$this->component->id, 60);
    $lock->get();
    try {
        expect(fn () => app(FinishEnvironmentDeployment::class)->finish($run->id))->toThrow(\Illuminate\Contracts\Cache\LockTimeoutException::class);
        SSH::assertNotExecutedContains('rm -rf');
    } finally {
        $lock->release();
    }
    app(FinishEnvironmentDeployment::class)->finish($run->id);
    expect($this->component->deployments()->whereNotNull('release')->orderBy('id')->pluck('release')->all())->toBe(['release-1', 'release-4', 'release-5']);
    expect($this->component->deployments()->count())->toBe(5);
    $deployments->first()->remove();
    expect($this->component->deployments()->count())->toBe(5);
});

test('deployment settings validate relative nonoverlapping shared paths and retention', function (array $paths) {
    expect(fn () => app(UpdateEnvironmentDeploymentSettings::class)->update($this->environment, ['shared_paths' => $paths, 'keep_releases' => 10]))->toThrow(ValidationException::class);
})->with([
    [['../outside']], [['/absolute']], [['storage/../outside']], [['.env']], [['.env.prev']], [['.env.next']], [['storage/.env']], [['storage', 'storage/cache']], [['a;touch pwned']], [['a//b']], [['.']], [['a', 'a']],
]);

test('deployment scripts can be overridden cleared and inherited without another owner', function () {
    $action = app(UpdateEnvironmentDeploymentScript::class);
    $action->update($this->environment, ['stage' => 'build', 'content' => 'echo environment']);
    $action->update($this->environment, ['stage' => 'build', 'content' => ''], $this->component);
    $this->assertDatabaseHas('deployment_scripts', ['component_id' => $this->component->id, 'environment_id' => null, 'site_id' => null, 'name' => 'build', 'content' => '']);
    $action->update($this->environment, ['stage' => 'build', 'content' => null, 'inherit' => true], $this->component);
    $this->assertDatabaseMissing('deployment_scripts', ['component_id' => $this->component->id]);
    app(UpdateEnvironmentDeploymentSettings::class)->update($this->environment, ['shared_paths' => ['uploads', 'var/cache'], 'keep_releases' => 4]);
    expect($this->environment->fresh()->shared_paths)->toBe(['uploads', 'var/cache'])->and($this->environment->fresh()->keep_releases)->toBe(4);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => true]);
});

test('release and script templates quote complete paths and preserve env recovery checkpoints', function () {
    $base = "/tmp/component's space;\$(touch injected)";
    $release = $base.'/releases/123';
    $rendered = view('ssh.modern-deployment.release', ['basePath' => $base, 'releasePath' => $release])->render();
    expect($rendered)->toContain('ln -sfn -- '.escapeshellarg($release).' '.escapeshellarg($base.'/current'));
    expect(view('ssh.os.run-script', ['path' => $release, 'script' => 'true'])->render())->toContain('cd -- '.escapeshellarg($release));
    $deployment = new Deployment;
    $deployment->id = 123;
    $releaseEnv = view('ssh.applications.release-env', ['component' => $this->component, 'deployment' => $deployment])->render();
    expect($releaseEnv)->toContain('if [ ! -f "$marker" ]', 'cp -p -- "$file" "$file.prev"', 'touch -- "$marker"', 'mv -f -- "$file.next" "$file"');
    $shared = view('ssh.applications.link-shared', ['component' => $this->component, 'releasePath' => $release, 'paths' => ['uploads']])->render();
    expect($shared)->toContain('if [ ! -e "$shared" ]', 'cp -a -- "$release" "$shared"', 'if [ -L "$target" ]', 'ln -s -- '.escapeshellarg($this->component->envPath()).' '.escapeshellarg($release.'/.env'));
});

test('clones serialize shared ssh config setup without mutating global git configuration', function (bool $usesToken) {
    $clone = view('ssh.git.clone', [
        'host' => 'github.com', 'repo' => escapeshellarg('git@github.com-component_1:owner/repo.git'),
        'path' => escapeshellarg('/home/runtime/releases/1'), 'branch' => escapeshellarg('main'),
        'key' => 'component_1', 'port' => 22, 'usesToken' => $usesToken,
    ])->render();
    expect($clone)->not->toContain('git config --global');
    if (! $usesToken) {
        expect($clone)->toContain('flock -w 60 9 || exit 1', ') 9>~/.ssh/config.vito.lock; then');
        expect(strpos($clone, 'config.vito.lock'))->toBeLessThan(strpos($clone, 'git clone'));
    }
})->with([false, true]);

test('failed barrier jobs report a safe advancement error instead of a cleanup error', function () {
    $run = app(DeployEnvironment::class)->run($this->environment);
    (new AdvanceEnvironmentDeploymentJob($run->id, $this->component->id, DeploymentStage::CLONE, false))->failed(new Exception('Private database error'));
    expect($this->environment->fresh()->last_error)->toContain('could not advance')->not->toContain('Private', 'cleanup');
});
