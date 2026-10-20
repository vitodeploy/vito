<?php

use App\Actions\Application\DeleteApplication;
use App\Actions\Application\UpdateApplication;
use App\Actions\Component\DeleteComponent;
use App\Actions\Component\PurgeServerComponents;
use App\Actions\Component\RestartComponentWorkers;
use App\Actions\Component\UpdateComponentCapabilities;
use App\Actions\Environment\CreateEnvironment;
use App\Actions\Environment\DeleteEnvironment;
use App\Actions\Environment\UpdateEnvironment;
use App\Actions\Environment\ValidateEnvironment;
use App\Actions\EnvironmentDeployment\AdvanceEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\AssertEnvironmentIdle;
use App\Actions\EnvironmentDeployment\CancelEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Actions\EnvironmentDeployment\FailStalledEnvironmentDeployments;
use App\Actions\EnvironmentDeployment\FinishEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\RollbackEnvironment;
use App\Actions\EnvironmentDeployment\RunComponentStage;
use App\Actions\EnvironmentDeployment\StartEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\UpdateEnvironmentDeploymentScript;
use App\Actions\EnvironmentDeployment\UpdateEnvironmentDeploymentSettings;
use App\Actions\EnvironmentVariable\UpsertEnvironmentVariable;
use App\Enums\ComponentStatus;
use App\Enums\DeploymentStage;
use App\Enums\DeploymentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Jobs\EnvironmentDeployment\DeployComponentStageJob;
use App\Jobs\EnvironmentDeployment\FinishEnvironmentDeploymentJob;
use App\Jobs\EnvironmentDeployment\StartEnvironmentDeploymentJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\SourceControl;
use App\SSH\OS\Git;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake('');
    Http::preventStrayRequests();
    Http::fake(['https://api.github.com/repos/*/commits/*' => Http::response(['sha' => str_repeat('a', 40), 'commit' => ['message' => 'Pinned']])]);
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $source = SourceControl::factory()->github()->create(['project_id' => $this->server->project_id, 'user_id' => $this->user->id]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'custom', 'source_control_id' => $source->id]);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    $this->environment->primary_component_id = $this->component->id;
    $this->environment->save();
});

function finishEnvironmentStage(EnvironmentDeployment $run, ?int $failure = null): void
{
    $run->refresh();
    $stage = $run->current_stage;
    foreach (app(AdvanceEnvironmentDeployment::class)->participants($run, $stage)->get() as $deployment) {
        if ($failure === $deployment->component_id) {
            app(RunComponentStage::class)->failed($deployment->id, $stage, $run->is_recovering);
        } else {
            app(RunComponentStage::class)->run($deployment->id, $stage, $run->is_recovering);
        }
        app(AdvanceEnvironmentDeployment::class)->advance($run->id, $stage, $run->is_recovering);
    }
    $run->refresh();
}

function startEnvironmentRun(Environment $environment): EnvironmentDeployment
{
    $run = app(DeployEnvironment::class)->run($environment);
    app(StartEnvironmentDeployment::class)->start($run->id);

    return $run->refresh();
}

test('deployment pins a commit once and runs barriered stages with primary only release commands', function () {
    $other = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    $run = startEnvironmentRun($this->environment);
    expect($run->deployments)->toHaveCount(2)->and($run->commit_id)->toBe(str_repeat('a', 40));
    $first = $run->deployments->first();
    app(RunComponentStage::class)->run($first->id, DeploymentStage::CLONE);
    app(AdvanceEnvironmentDeployment::class)->advance($run->id, DeploymentStage::CLONE);
    expect($run->fresh()->current_stage)->toBe(DeploymentStage::CLONE);
    finishEnvironmentStage($run);
    expect($run->current_stage)->toBe(DeploymentStage::BUILD);
    $count = Queue::pushed(DeployComponentStageJob::class)->count();
    app(AdvanceEnvironmentDeployment::class)->advance($run->id, DeploymentStage::CLONE);
    expect(Queue::pushed(DeployComponentStageJob::class)->count())->toBe($count);
    finishEnvironmentStage($run);
    expect($run->current_stage)->toBe(DeploymentStage::PRE_RELEASE);
    expect(app(AdvanceEnvironmentDeployment::class)->participants($run, DeploymentStage::PRE_RELEASE)->pluck('component_id')->all())->toBe([$this->component->id]);
    finishEnvironmentStage($run);
    finishEnvironmentStage($run);
    expect($this->environment->fresh()->live_run_id)->toBe($run->id);
    finishEnvironmentStage($run);
    expect($run->status)->toBe(EnvironmentDeploymentStatus::FINISHED)->and($run->finished_at)->not->toBeNull();
    $this->assertDatabaseHas('components', ['id' => $other->id, 'needs_redeploy' => false]);
    SSH::assertExecutedContains('git checkout -f '.escapeshellarg(str_repeat('a', 40)));
    Http::assertSentCount(1);
    Event::assertDispatched(SocketEvent::class, fn ($event): bool => $event->data->type === 'environment-deployment.updated');
});

test('deploy requires a ready primary app component', function () {
    $this->environment->primary_component_id = null;
    $this->environment->save();
    expect(fn () => app(DeployEnvironment::class)->run($this->environment))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('environment_deployments', 0);
});

test('pending deployments supersede older pending runs and resolve their SHA only when started', function () {
    $running = startEnvironmentRun($this->environment);
    $old = app(DeployEnvironment::class)->run($this->environment);
    $next = app(DeployEnvironment::class)->run($this->environment);
    expect($old->fresh()->status)->toBe(EnvironmentDeploymentStatus::SUPERSEDED)->and($next->commit_id)->toBeNull();
    Http::assertSentCount(1);
    finishEnvironmentStage($running, $this->component->id);
    app(FinishEnvironmentDeployment::class)->finish($running->id);
    expect($next->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING);
    Queue::assertPushed(StartEnvironmentDeploymentJob::class, fn ($job): bool => $job->runId === $next->id);
});

test('failure before release does not activate the release and restores redeploy state', function () {
    $run = startEnvironmentRun($this->environment);
    finishEnvironmentStage($run);
    finishEnvironmentStage($run, $this->component->id);
    expect($run->status)->toBe(EnvironmentDeploymentStatus::FAILED)->and($this->environment->fresh()->live_run_id)->toBeNull();
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => true]);
    SSH::assertNotExecutedContains('mv -f -- "$file.next" "$file"');
});

test('release failure rolls back only after all participants settle', function (bool $rollbackFails) {
    $other = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    $run = startEnvironmentRun($this->environment);
    finishEnvironmentStage($run);
    finishEnvironmentStage($run);
    finishEnvironmentStage($run);
    $first = $run->deployments()->where('component_id', $this->component->id)->sole();
    app(RunComponentStage::class)->failed($first->id, DeploymentStage::RELEASE);
    app(AdvanceEnvironmentDeployment::class)->advance($run->id, DeploymentStage::RELEASE);
    expect($run->fresh()->is_recovering)->toBeFalse();
    finishEnvironmentStage($run);
    expect($run->is_recovering)->toBeTrue();
    finishEnvironmentStage($run, $rollbackFails ? $other->id : null);
    expect($run->status)->toBe($rollbackFails ? EnvironmentDeploymentStatus::PARTIAL : EnvironmentDeploymentStatus::ROLLED_BACK);
    expect($this->environment->fresh()->live_run_id)->toBeNull();
})->with([false, true]);

test('post release failure preserves the live release', function () {
    $run = startEnvironmentRun($this->environment);
    for ($i = 0; $i < 4; $i++) {
        finishEnvironmentStage($run);
    }
    finishEnvironmentStage($run, $this->component->id);
    expect($run->status)->toBe(EnvironmentDeploymentStatus::FAILED)->and($this->environment->fresh()->live_run_id)->toBe($run->id);
    $this->assertDatabaseHas('deployments', ['environment_deployment_id' => $run->id, 'active' => true]);
});

test('edits during deployment are retained and snapshots are encrypted and never exposed', function () {
    app(UpsertEnvironmentVariable::class)->upsert($this->environment, ['key' => 'SECRET', 'value' => 'original-secret', 'is_secret' => true]);
    $run = startEnvironmentRun($this->environment);
    $variable = $this->environment->variables()->sole();
    app(UpsertEnvironmentVariable::class)->upsert($this->environment, ['key' => 'SECRET', 'value' => 'new-secret', 'is_secret' => true], $variable);
    while (! $run->status->isTerminal()) {
        finishEnvironmentStage($run);
    }
    expect($run->variables[0]['value'])->toBe('original-secret')->and($run->getRawOriginal('variables'))->not->toContain('original-secret');
    expect($run->toArray())->not->toHaveKey('variables');
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => true]);
    SSH::assertNotExecutedContains('original-secret');
    SSH::assertNotExecutedContains('new-secret');
});

test('new components join the live SHA without a primary or release command and do not move live run', function () {
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    $new = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    $this->environment->primary_component_id = null;
    $this->environment->save();
    $join = app(DeployEnvironment::class)->join($this->environment);
    app(StartEnvironmentDeployment::class)->start($join->id);
    expect($join->fresh()->deployments->pluck('component_id')->all())->toBe([$new->id]);
    $stages = [];
    $join->refresh();
    while (! $join->status->isTerminal()) {
        $stages[] = $join->current_stage;
        finishEnvironmentStage($join);
    }
    expect($stages)->not->toContain(DeploymentStage::PRE_RELEASE)->and($join->commit_id)->toBe($live->commit_id)->and($join->release)->toBe($live->release);
    expect($this->environment->fresh()->live_run_id)->toBe($live->id);
    Http::assertSentCount(1);
});

test('never deployed environments do not join', function () {
    expect(app(DeployEnvironment::class)->join($this->environment))->toBeNull();
});

test('pending runs resolve a new head and include components installed during the prior run', function () {
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(['https://api.github.com/repos/*/commits/*' => Http::sequence()
        ->push(['sha' => str_repeat('a', 40), 'commit' => ['message' => 'First']])
        ->push(['sha' => str_repeat('b', 40), 'commit' => ['message' => 'Second']])]);
    $running = startEnvironmentRun($this->environment);
    $pending = app(DeployEnvironment::class)->run($this->environment);
    $new = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    expect(app(DeployEnvironment::class)->join($this->environment))->toBeNull();
    finishEnvironmentStage($running, $this->component->id);
    app(FinishEnvironmentDeployment::class)->finish($running->id);
    app(StartEnvironmentDeployment::class)->start($pending->id);
    app(StartEnvironmentDeployment::class)->start($pending->id);
    expect($pending->fresh()->commit_id)->toBe(str_repeat('b', 40))
        ->and($pending->fresh()->deployments->pluck('component_id')->all())->toContain($new->id);
    Http::assertSentCount(2);
});

test('components installed during a run join the resulting live release', function (bool $failed) {
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    $run = startEnvironmentRun($this->environment);
    $new = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    expect(app(DeployEnvironment::class)->join($this->environment))->toBeNull();
    while (! $run->status->isTerminal()) {
        finishEnvironmentStage($run, $failed ? $this->component->id : null);
    }
    app(FinishEnvironmentDeployment::class)->finish($run->id);
    $join = $this->environment->deployments()->where('is_join', true)->sole();
    app(StartEnvironmentDeployment::class)->start($join->id);
    expect($join->release)->toBe($failed ? $live->release : $run->release)
        ->and($join->fresh()->deployments->pluck('component_id')->all())->toBe([$new->id]);
})->with([false, true]);

test('a failed join is not retried and a full deployment requested during a join is pending', function () {
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    $new = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    $join = app(DeployEnvironment::class)->join($this->environment);
    app(StartEnvironmentDeployment::class)->start($join->id);
    $pending = app(DeployEnvironment::class)->run($this->environment);
    expect($pending->status)->toBe(EnvironmentDeploymentStatus::PENDING);
    finishEnvironmentStage($join, $new->id);
    app(FinishEnvironmentDeployment::class)->finish($join->id);
    expect($pending->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING);
    expect($this->environment->deployments()->where('is_join', true)->count())->toBe(1);
    app(StartEnvironmentDeployment::class)->failed($pending->id);
    app(FinishEnvironmentDeployment::class)->finish($pending->id);
    expect(app(DeployEnvironment::class)->join($this->environment))->toBeNull();
    $this->assertDatabaseHas('components', ['id' => $new->id, 'needs_redeploy' => true]);
});

test('deployment pages expose stages and safe script metadata with scoped permissions', function () {
    $base = '/applications/'.$this->application->id.'/environments/'.$this->environment->id;
    $this->actingAs($this->user)->put($base.'/deployment-settings', ['shared_paths' => ['uploads'], 'keep_releases' => 3])->assertRedirect()->assertSessionHasNoErrors();
    $this->put($base.'/deployment-scripts', ['stage' => 'build', 'content' => 'echo build'])->assertRedirect()->assertSessionHasNoErrors();
    $this->put($base.'/components/'.$this->component->id.'/deployment-scripts', ['stage' => 'build', 'content' => 'echo override'])->assertRedirect()->assertSessionHasNoErrors();
    $this->get($base.'/deployment-settings')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('applications/environments/deployment-settings')->has('scripts', 1)->where('canUpdate', true)->where('deploymentActive', false));
    $this->get($base.'/components/'.$this->component->id.'/deployment-scripts')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('applications/environments/deployment-settings')->has('inheritedScripts', 1)->where('scriptComponent.id', $this->component->id));
    $this->post($base.'/deployments')->assertRedirect();
    $run = $this->environment->deployments()->sole();
    app(StartEnvironmentDeployment::class)->start($run->id);
    $this->get($base.'/deployments')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('applications/environments/deployments')->has('deployments.data', 1)->missing('deployments.data.0.variables')->where('environment.can_deploy', true));
    $this->get($base.'/deployments/'.$run->id)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('applications/environments/deployment')->where('deployment.current_stage', 'clone')->has('deployment.deployments', 1)->missing('deployment.variables'));
    $foreign = Environment::factory()->for($this->application)->create();
    $this->get(str_replace('/environments/'.$this->environment->id, '/environments/'.$foreign->id, $base).'/deployments/'.$run->id)->assertNotFound();
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->get($base.'/deployments')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('environment.can_deploy', false));
    $this->postJson($base.'/deployments')->assertForbidden();
    $this->putJson($base.'/deployment-settings', ['shared_paths' => [], 'keep_releases' => 1])->assertForbidden();
    $this->putJson($base.'/deployment-scripts', ['stage' => 'build', 'content' => ''])->assertForbidden();
    $this->putJson($base.'/components/'.$this->component->id.'/deployment-scripts', ['stage' => 'build', 'content' => ''])->assertForbidden();
});

test('nonterminal runs block conflicting mutations but permit variable edits', function (string $operation, EnvironmentDeploymentStatus $status) {
    Http::fake(['https://api.github.com/repos/owner/changed' => Http::response([])]);
    EnvironmentDeployment::factory()->for($this->environment)->create(['status' => $status]);
    $mutation = match ($operation) {
        'component' => fn () => app(DeleteComponent::class)->delete($this->component, []),
        'capabilities' => fn () => app(UpdateComponentCapabilities::class)->update($this->component, ['capabilities' => []]),
        'runtime' => fn () => app(UpdateEnvironment::class)->update($this->environment, ['name' => $this->environment->name, 'branch' => 'other', 'runtime_version' => null]),
        'repository' => fn () => app(UpdateApplication::class)->update($this->application, ['name' => $this->application->name, 'source_control_id' => $this->application->source_control_id, 'repository' => 'owner/changed'], $this->user),
        'environment' => fn () => app(DeleteEnvironment::class)->delete($this->environment, ['name' => $this->environment->name]),
        'application' => fn () => app(DeleteApplication::class)->delete($this->application, ['name' => $this->application->name]),
        'server' => fn () => app(PurgeServerComponents::class)->purge($this->server),
        'settings' => fn () => app(UpdateEnvironmentDeploymentSettings::class)->update($this->environment, ['shared_paths' => [], 'keep_releases' => 2]),
        'scripts' => fn () => app(UpdateEnvironmentDeploymentScript::class)->update($this->environment, ['stage' => 'build', 'content' => 'true']),
    };
    expect($mutation)->toThrow(ValidationException::class);
    app(UpsertEnvironmentVariable::class)->upsert($this->environment, ['key' => 'EDIT_DURING_RUN', 'value' => 'next', 'is_secret' => false]);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'status' => ComponentStatus::READY]);
    $this->assertDatabaseHas('environment_variables', ['environment_id' => $this->environment->id, 'key' => 'EDIT_DURING_RUN']);
})->with(['component', 'capabilities', 'runtime', 'repository', 'environment', 'application', 'server', 'settings', 'scripts'])->with([EnvironmentDeploymentStatus::PENDING, EnvironmentDeploymentStatus::RUNNING]);

test('new environments prefill editable scripts and shared paths from their preset', function (string $runtime) {
    $this->application->update(['runtime' => $runtime]);
    $environment = app(CreateEnvironment::class)->create($this->application, ['name' => 'Preview', 'branch' => 'main', 'runtime_version' => $runtime === 'node' ? '22' : null]);
    expect($environment->shared_paths)->toBe($this->application->preset()->sharedPaths());
    foreach ($this->application->preset()->defaultScripts() as $stage => $content) {
        $this->assertDatabaseHas('deployment_scripts', ['environment_id' => $environment->id, 'site_id' => null, 'component_id' => null, 'name' => $stage, 'content' => $content]);
    }
})->with(['node', 'custom']);

test('environment branches reject control characters and option prefixes before deployment', function (string $branch) {
    expect(fn () => app(ValidateEnvironment::class)->validate($this->application, [
        'name' => $this->environment->name, 'branch' => $branch, 'runtime_version' => null,
    ], $this->environment))->toThrow(ValidationException::class);
})->with(["main#\nEOF\ntouch /tmp/injected\n#", "main\rnext", "main\x00next", 'main branch', '--upload-pack=other']);

test('deployment head requests encode the branch as a single provider ref', function () {
    $this->environment->branch = 'feature/next#preview&test';
    $this->environment->save();
    startEnvironmentRun($this->environment);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.github.com/repos/'.$this->application->repository.'/commits/'.rawurlencode($this->environment->branch));
});

test('cleanup timeout failures still promote pending runs exactly once', function () {
    $run = startEnvironmentRun($this->environment);
    $pending = app(DeployEnvironment::class)->run($this->environment);
    finishEnvironmentStage($run, $this->component->id);
    $job = new FinishEnvironmentDeploymentJob($run->id);
    $job->failed(new Exception('Private cleanup failure'));
    $job->failed(new Exception('Duplicate failure callback'));
    expect($pending->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING)
        ->and($this->environment->fresh()->last_error)->toContain('cleanup failed')->not->toContain('Private')
        ->and($job->failOnTimeout)->toBeTrue()
        ->and((new ReflectionMethod($job, 'lockSeconds'))->invoke($job))->toBeGreaterThan($job->timeout);
    expect(Queue::pushed(StartEnvironmentDeploymentJob::class)->filter(fn ($queued): bool => $queued->runId === $pending->id))->toHaveCount(1);
});

test('stage deadlines and job timeouts are bounded by the ssh reservation lifetime', function () {
    $this->freezeTime();
    $run = startEnvironmentRun($this->environment);
    foreach (DeploymentStage::cases() as $stage) {
        app(AdvanceEnvironmentDeployment::class)->dispatch($run, $stage);
        $job = Queue::pushed(DeployComponentStageJob::class)->last();
        expect($job->timeout)->toBe(config('component.deploy.stage_timeouts.'.$stage->value))
            ->toBeLessThan(config('queue.connections.ssh.retry_after') - 60)
            ->and($job->deadline)->toBe(now()->addSeconds($job->timeout)->timestamp)
            ->and($run->fresh()->stage_deadline_at->timestamp)->toBe($job->deadline);
    }
    config(['component.deploy.stage_timeouts.clone' => 99999]);
    expect(DeploymentStage::CLONE->timeout())->toBeLessThan(config('queue.connections.ssh.retry_after') - 60);
    $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => str_contains($event->command ?? '', 'deployments:check-stalled'));
    expect($event)->not->toBeNull()->and($event->expression)->toBe('* * * * *')->and($event->withoutOverlapping)->toBeTrue()->and($event->expiresAt)->toBe(5);
});

test('the watchdog fails vanished stages and startup jobs and resumes a pending successor', function (bool $started) {
    $run = $started ? startEnvironmentRun($this->environment) : app(DeployEnvironment::class)->run($this->environment);
    $pending = app(DeployEnvironment::class)->run($this->environment);
    $this->travel(601)->seconds();
    $this->artisan('deployments:check-stalled')->assertSuccessful();
    expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::FAILED)
        ->and($run->fresh()->last_error)->toContain('deadline')
        ->and($pending->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING);
    $this->artisan('deployments:check-stalled')->assertSuccessful();
    expect(Queue::pushed(StartEnvironmentDeploymentJob::class)->filter(fn ($job): bool => $job->runId === $pending->id))->toHaveCount(1);
})->with([false, true]);

test('an overdue release recovers and an overdue recovery becomes partial', function () {
    $run = startEnvironmentRun($this->environment);
    for ($i = 0; $i < 3; $i++) {
        finishEnvironmentStage($run);
    }
    $this->travel(301)->seconds();
    app(FailStalledEnvironmentDeployments::class)->handle();
    expect($run->fresh()->is_recovering)->toBeTrue()->and($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING);
    $this->travel(301)->seconds();
    app(FailStalledEnvironmentDeployments::class)->handle();
    expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::PARTIAL)->and($run->fresh()->stage_deadline_at)->toBeNull();
});

test('watchdog waits for a live component mutex and late stage jobs do not run ssh', function () {
    $run = startEnvironmentRun($this->environment);
    $job = Queue::pushed(DeployComponentStageJob::class)->last();
    $lock = Cache::lock('unique-queue:component-'.$this->component->id, 1260);
    $lock->get();
    $this->travel(601)->seconds();
    app(FailStalledEnvironmentDeployments::class)->handle();
    expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING);
    $lock->release();
    $job->handle();
    expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::FAILED);
    SSH::assertNotExecutedContains('git clone');
});

test('pending cancellation does not affect the running deployment', function () {
    $running = startEnvironmentRun($this->environment);
    $pending = app(DeployEnvironment::class)->run($this->environment);
    app(CancelEnvironmentDeployment::class)->cancel($pending);
    app(FinishEnvironmentDeployment::class)->finish($pending->id);
    expect($pending->fresh()->status)->toBe(EnvironmentDeploymentStatus::CANCELLED)
        ->and($running->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING);
});

test('cancellation drains running work before accepting a successor or conflicting mutations', function () {
    $run = startEnvironmentRun($this->environment);
    $job = Queue::pushed(DeployComponentStageJob::class)->last();
    $this->mock(Git::class)->shouldReceive('cloneRepository')->once()->andReturnUsing(function () use ($run): void {
        app(CancelEnvironmentDeployment::class)->cancel($run);
        $this->pendingRun = app(DeployEnvironment::class)->run($this->environment);
        app(FinishEnvironmentDeployment::class)->next($this->environment);
        expect($this->pendingRun->fresh()->status)->toBe(EnvironmentDeploymentStatus::PENDING);
        expect(fn () => app(AssertEnvironmentIdle::class)->validate($this->environment, 'name'))->toThrow(ValidationException::class);
    });
    $job->handle();
    expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::CANCELLED)->and($run->fresh()->stage_deadline_at)->toBeNull();
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'needs_redeploy' => true]);
    app(FinishEnvironmentDeployment::class)->finish($run->id);
    expect($this->pendingRun->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING);
    expect(Queue::pushed(DeployComponentStageJob::class))->toHaveCount(1);
});

test('cancelled queued jobs settle without ssh and vanished cancelled jobs are reaped', function (bool $vanished) {
    $run = startEnvironmentRun($this->environment);
    app(CancelEnvironmentDeployment::class)->cancel($run);
    if ($vanished) {
        $this->travel(601)->seconds();
        app(FailStalledEnvironmentDeployments::class)->handle();
    } else {
        Queue::pushed(DeployComponentStageJob::class)->last()->handle();
    }
    expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::CANCELLED)->and($run->fresh()->stage_deadline_at)->toBeNull();
    SSH::assertNotExecutedContains('git clone');
})->with([false, true]);

test('cancellation is rejected at and after release', function (DeploymentStage $stage) {
    $run = startEnvironmentRun($this->environment);
    app(AdvanceEnvironmentDeployment::class)->dispatch($run, $stage);
    expect(fn () => app(CancelEnvironmentDeployment::class)->cancel($run))->toThrow(ValidationException::class);
})->with([DeploymentStage::RELEASE, DeploymentStage::POST_RELEASE]);

test('rollback activates only retained code and keeps live variables for later joins', function () {
    app(UpsertEnvironmentVariable::class)->upsert($this->environment, ['key' => 'VALUE', 'value' => 'old', 'is_secret' => false]);
    $target = startEnvironmentRun($this->environment);
    while (! $target->status->isTerminal()) {
        finishEnvironmentStage($target);
    }
    app(UpsertEnvironmentVariable::class)->upsert($this->environment, ['key' => 'VALUE', 'value' => 'live', 'is_secret' => false], $this->environment->variables()->sole());
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    app(UpsertEnvironmentVariable::class)->upsert($this->environment, ['key' => 'VALUE', 'value' => 'unapplied', 'is_secret' => false], $this->environment->variables()->sole());
    $new = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'worker']);
    $this->mock(RestartComponentWorkers::class)->shouldReceive('restart')->once();
    SSH::fake('');
    Http::fake();
    $rollback = app(RollbackEnvironment::class)->run($this->environment, $target, $this->user);
    app(StartEnvironmentDeployment::class)->start($rollback->id);
    $rollback->refresh();
    expect($rollback->current_stage)->toBe(DeploymentStage::RELEASE)->and($rollback->deployments->pluck('component_id')->all())->toBe([$this->component->id]);
    finishEnvironmentStage($rollback);
    expect($rollback->status)->toBe(EnvironmentDeploymentStatus::FINISHED)->and($rollback->rollback_of_id)->toBe($target->id)
        ->and($rollback->variables)->toBe($live->variables)->and($this->environment->fresh()->live_run_id)->toBe($rollback->id);
    SSH::assertExecutedContains('ln -sfn -- '.escapeshellarg($this->component->releasesPath().'/'.$target->release));
    SSH::assertNotExecutedContains('file.next');
    SSH::assertNotExecutedContains('base64 -d');
    SSH::assertNotExecutedContains('git clone');
    Http::assertNothingSent();
    app(FinishEnvironmentDeployment::class)->finish($rollback->id);
    $join = $this->environment->deployments()->where('is_join', true)->sole();
    app(StartEnvironmentDeployment::class)->start($join->id);
    expect($join->release)->toBe($target->release)->and($join->variables)->toBe($live->variables)
        ->and($join->fresh()->deployments->pluck('component_id')->all())->toBe([$new->id]);
});

test('rollback rejects foreign join unfinished and pruned targets', function (string $invalid) {
    $target = startEnvironmentRun($this->environment);
    while (! $target->status->isTerminal()) {
        finishEnvironmentStage($target);
    }
    match ($invalid) {
        'foreign' => $target->environment_id = Environment::factory()->for($this->application)->create()->id,
        'join' => $target->is_join = true,
        'unfinished' => $target->status = EnvironmentDeploymentStatus::FAILED,
        'pruned' => $target->deployments()->update(['release' => null]),
    };
    $target->save();
    expect(fn () => app(RollbackEnvironment::class)->run($this->environment, $target))->toThrow(ValidationException::class);
})->with(['foreign', 'join', 'unfinished', 'pruned']);

test('queued rollbacks protect release names and retain active duplicate releases when pruning', function () {
    $this->environment->keep_releases = 1;
    $this->environment->save();
    $target = startEnvironmentRun($this->environment);
    while (! $target->status->isTerminal()) {
        finishEnvironmentStage($target);
    }
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    $active = startEnvironmentRun($this->environment);
    $pending = app(DeployEnvironment::class)->run($this->environment);
    $rollback = app(RollbackEnvironment::class)->run($this->environment, $target);
    expect($rollback->status)->toBe(EnvironmentDeploymentStatus::PENDING)->and($pending->fresh()->status)->toBe(EnvironmentDeploymentStatus::SUPERSEDED);
    app(FinishEnvironmentDeployment::class)->finish($live->id);
    expect($target->deployments()->sole()->release)->toBe($target->release);
    finishEnvironmentStage($active, $this->component->id);
    app(FinishEnvironmentDeployment::class)->finish($active->id);
    app(StartEnvironmentDeployment::class)->start($rollback->id);
    finishEnvironmentStage($rollback);
    app(FinishEnvironmentDeployment::class)->finish($rollback->id);
    expect($target->deployments()->sole()->release)->toBe($target->release);
    SSH::assertNotExecutedContains('rm -rf -- '.escapeshellarg($this->component->releasesPath().'/'.$target->release));
});

test('rollback failure restores code without restoring a previous environment file', function () {
    $target = startEnvironmentRun($this->environment);
    while (! $target->status->isTerminal()) {
        finishEnvironmentStage($target);
    }
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    $rollback = app(RollbackEnvironment::class)->run($this->environment, $target);
    app(StartEnvironmentDeployment::class)->start($rollback->id);
    $deployment = $rollback->deployments()->sole();
    $deployment->release_started = true;
    $deployment->save();
    finishEnvironmentStage($rollback, $this->component->id);
    SSH::fake('');
    finishEnvironmentStage($rollback);
    expect($rollback->status)->toBe(EnvironmentDeploymentStatus::ROLLED_BACK)->and($this->environment->fresh()->live_run_id)->toBe($live->id);
    SSH::assertExecutedContains('ln -sfn -- '.escapeshellarg($this->component->releasesPath().'/'.$live->release));
    SSH::assertNotExecutedContains('file.prev');
});

test('cancelling joins leaves new components empty and does not retry the join', function (bool $started) {
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    $new = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    $join = app(DeployEnvironment::class)->join($this->environment);
    if ($started) {
        app(StartEnvironmentDeployment::class)->start($join->id);
    }
    app(CancelEnvironmentDeployment::class)->cancel($join);
    if ($started) {
        Queue::pushed(DeployComponentStageJob::class)->last()->handle();
    }
    app(FinishEnvironmentDeployment::class)->finish($join->id);
    expect($new->deployments()->where('active', true)->exists())->toBeFalse()->and($this->environment->deployments()->where('is_join', true)->count())->toBe(1);
})->with([false, true]);

test('operation endpoints enforce scoped policies and expose safe run controls', function () {
    $base = '/applications/'.$this->application->id.'/environments/'.$this->environment->id.'/deployments/';
    $run = startEnvironmentRun($this->environment);
    $this->actingAs($this->user)->post($base.$run->id.'/cancel')->assertRedirect();
    $this->postJson($base.$run->id.'/cancel')->assertUnprocessable();
    Queue::pushed(DeployComponentStageJob::class)->last()->handle();
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    $this->get($base.$live->id)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('deployment.cancellable', false)->where('deployment.rollback_available', true)->where('deployment.rollback_of_id', null));
    $this->post($base.$live->id.'/rollback')->assertRedirect();
    $foreign = EnvironmentDeployment::factory()->create();
    $this->postJson($base.$foreign->id.'/cancel')->assertNotFound();
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->postJson($base.$live->id.'/rollback')->assertForbidden();
    $this->postJson($base.$live->id.'/cancel')->assertForbidden();
});

test('rollback startup fails safely when a retained release disappeared from disk', function () {
    $target = startEnvironmentRun($this->environment);
    while (! $target->status->isTerminal()) {
        finishEnvironmentStage($target);
    }
    $rollback = app(RollbackEnvironment::class)->run($this->environment, $target);
    SSH::fake('')->execWillFail();
    $job = new StartEnvironmentDeploymentJob($rollback->id);
    $queueJob = Mockery::mock(Job::class);
    $queueJob->shouldReceive('fail')->once()->andReturnUsing(fn ($error) => $job->failed($error));
    $job->setJob($queueJob);
    $job->handle();
    expect($rollback->fresh()->status)->toBe(EnvironmentDeploymentStatus::FAILED)
        ->and($rollback->deployments()->count())->toBe(0)
        ->and($this->environment->fresh()->live_run_id)->toBe($target->id);
    SSH::assertNotExecutedContains('ln -sfn');
});

test('an unstarted cancelled or expired join is not recreated by unrelated runs or later component installs', function (bool $cancelled) {
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    $new = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    $join = app(DeployEnvironment::class)->join($this->environment);
    $pending = app(DeployEnvironment::class)->run($this->environment);
    app(CancelEnvironmentDeployment::class)->cancel($pending);
    if ($cancelled) {
        app(CancelEnvironmentDeployment::class)->cancel($join);
    } else {
        $this->travel(601)->seconds();
        app(FailStalledEnvironmentDeployments::class)->handle();
    }
    app(FinishEnvironmentDeployment::class)->finish($pending->id);
    app(FinishEnvironmentDeployment::class)->finish($join->id);
    $this->assertDatabaseHas('deployments', ['component_id' => $new->id, 'environment_deployment_id' => $join->id, 'release' => null, 'active' => false, 'status' => DeploymentStatus::FAILED]);
    expect($this->environment->deployments()->where('is_join', true)->count())->toBe(1);
    expect(app(DeployEnvironment::class)->join($this->environment))->toBeNull();
    $later = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    $nextJoin = app(DeployEnvironment::class)->join($this->environment);
    app(StartEnvironmentDeployment::class)->start($nextJoin->id);
    expect($nextJoin->deployments()->pluck('component_id')->all())->toBe([$later->id]);
})->with([false, true]);

test('retention holds the admission locks and pruned releases cannot be admitted for rollback', function () {
    $this->environment->keep_releases = 1;
    $this->environment->save();
    $target = startEnvironmentRun($this->environment);
    while (! $target->status->isTerminal()) {
        finishEnvironmentStage($target);
    }
    $live = startEnvironmentRun($this->environment);
    while (! $live->status->isTerminal()) {
        finishEnvironmentStage($live);
    }
    $locks = [];
    foreach ([Application::class, Environment::class] as $model) {
        $model::addGlobalScope('verify-retention-locks', function (Builder $query) use (&$locks, $model): void {
            if ($query->getQuery()->lock === true) {
                $locks[] = $model;
            }
        });
    }
    app(FinishEnvironmentDeployment::class)->finish($live->id);
    expect(array_slice($locks, 0, 2))->toBe([Application::class, Environment::class]);
    $this->assertDatabaseHas('deployments', ['environment_deployment_id' => $target->id, 'release' => null]);
    expect(fn () => app(RollbackEnvironment::class)->run($this->environment, $target))->toThrow(ValidationException::class);
});

test('release activation fails closed when its release identifier is missing', function () {
    $run = startEnvironmentRun($this->environment);
    app(AdvanceEnvironmentDeployment::class)->dispatch($run, DeploymentStage::RELEASE);
    $deployment = $run->deployments()->sole();
    $deployment->release = null;
    $deployment->save();
    SSH::fake('');
    expect(fn () => app(RunComponentStage::class)->run($deployment->id, DeploymentStage::RELEASE))->toThrow(RuntimeException::class, 'The deployment release is unavailable.');
    $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'active' => false, 'release_started' => false]);
    expect(SSH::getExecutedCommands())->toBeEmpty();
});
