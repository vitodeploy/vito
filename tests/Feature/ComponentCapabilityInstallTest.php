<?php

use App\Actions\Component\CreateComponent;
use App\Actions\Component\DeleteComponent;
use App\Actions\Component\InstallComponent;
use App\Actions\Component\RetryComponentInstall;
use App\Actions\Component\SyncComponentCapabilities;
use App\Actions\Component\SyncComponentRuntime;
use App\Actions\Component\UpdateComponentCapabilities;
use App\Actions\ComponentCapability\InstallWebCapability;
use App\Actions\Environment\UpdateEnvironment;
use App\Actions\PHP\EnsureFpmPool;
use App\Actions\Worker\CreateWorker;
use App\Enums\ComponentStatus;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Helpers\ComponentShell;
use App\Jobs\Component\SyncComponentCapabilitiesJob;
use App\Jobs\Component\SyncComponentRuntimeJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\CronJob;
use App\Models\Environment;
use App\Models\SourceControl;
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
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'source_control_id' => SourceControl::factory()->githubApp()->create()->id]);
    $this->environment = Environment::factory()->for($this->application)->create(['runtime_version' => '22']);
});

function installCapabilityComponent(array $capabilities = [], string $user = 'runtime'): Component
{
    return app(CreateComponent::class)->create(test()->environment, ['name' => 'Runtime', 'server_id' => test()->server->id, 'user' => $user, 'type' => 'app', 'capabilities' => $capabilities]);
}

test('proxy install creates one stopped managed app for non-framework runtimes and can uninstall', function (string $runtime) {
    $this->application->update(['runtime' => $runtime]);
    if ($runtime === 'custom') {
        $this->environment->update(['runtime_version' => null]);
    }
    $component = installCapabilityComponent([['capability' => 'web', 'config' => ['start_command' => './server --listen "$PORT"', 'port' => 14000]]]);
    app(InstallComponent::class)->install($component->id);
    $capability = $component->capabilities()->sole();
    $worker = $component->workers()->sole();
    $capability->handler()->install($component->fresh());
    expect($component->workers()->count())->toBe(1);
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'component_capability_id' => $capability->id, 'name' => 'app', 'status' => 'stopped', 'command' => './server --listen "$PORT"']);
    $this->assertDatabaseHas('component_capabilities', ['id' => $capability->id, 'status' => 'ready']);
    expect(SSH::getUploadedContent())->toContain('command='.ComponentShell::wrap($component, $worker->command), 'autostart=false')->not->toContain('environment=');
    SSH::assertNotExecutedContains('supervisorctl start');
    app(DeleteComponent::class)->delete($component, []);
    app(DeleteComponent::class)->handle($component->id);
    $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
    $this->assertDatabaseMissing('components', ['id' => $component->id]);
})->with(['node', 'custom']);

test('static web creates no pool or process', function () {
    $this->application->update(['runtime' => 'static']);
    $this->environment->update(['runtime_version' => null]);
    $component = installCapabilityComponent([['capability' => 'web', 'config' => ['web_directory' => 'dist']]]);
    $this->mock(EnsureFpmPool::class)->shouldNotReceive('ensure');
    app(InstallComponent::class)->install($component->id);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'ready']);
    expect($component->workers()->count())->toBe(0)->and($component->cronJobs()->count())->toBe(0);
});

test('fpm capability skips the default user and provisions only the requested version', function (bool $defaultUser) {
    $this->application->update(['runtime' => 'php']);
    $this->environment->update(['runtime_version' => '8.2']);
    $component = installCapabilityComponent([['capability' => 'web']], $defaultUser ? $this->server->getSshUser() : 'runtime');
    $capability = $component->capabilities()->sole();
    $pool = $this->mock(EnsureFpmPool::class);
    if ($defaultUser) {
        $pool->shouldNotReceive('ensure');
    } else {
        $pool->shouldReceive('ensure')->once()->withArgs(fn ($server, $user, $version) => $server->id === $this->server->id && $user === 'runtime' && $version === '8.2');
    }
    $capability->handler()->install($component);
})->with([false, true]);

test('fpm uninstall retains pools used by Sites or another components installed runtime', function (string $shared) {
    $this->application->update(['runtime' => 'php']);
    $this->environment->update(['runtime_version' => '8.2']);
    $component = installCapabilityComponent([['capability' => 'web']]);
    if ($shared === 'site') {
        $this->site->update(['isolated_user_id' => $component->isolated_user_id, 'user' => 'runtime', 'php_version' => '8.2']);
    } elseif ($shared === 'component') {
        $otherEnvironment = Environment::factory()->for($this->application)->create(['runtime_version' => '8.3']);
        $other = Component::factory()->for($otherEnvironment)->for($this->server)->create(['isolated_user_id' => $component->isolated_user_id, 'user' => 'runtime', 'installed_runtime_version' => '8.2']);
        ComponentCapability::factory()->for($other)->create(['capability' => 'web']);
    }
    $component->capabilities()->sole()->handler()->uninstall($component);
    if ($shared === 'none') {
        SSH::assertExecutedContains('/etc/php/8.2/fpm/pool.d/runtime.conf');
    } else {
        SSH::assertNotExecutedContains('/etc/php/8.2/fpm/pool.d/runtime.conf');
    }
})->with(['none', 'site', 'component']);

test('scheduler installs and updates exactly one configured every-minute line', function () {
    $component = installCapabilityComponent([['capability' => 'scheduler', 'config' => ['command' => 'node custom-schedule.mjs']]]);
    app(InstallComponent::class)->install($component->id);
    $capability = $component->capabilities()->sole();
    $capability->config = ['command' => './scheduler --tick'];
    $capability->save();
    $capability->handler()->install($component->fresh());
    $cron = $component->cronJobs()->sole();
    $this->assertDatabaseHas('cron_jobs', ['id' => $cron->id, 'component_capability_id' => $capability->id, 'frequency' => '* * * * *', 'command' => './scheduler --tick', 'user' => 'runtime']);
    expect(CronJob::crontab($this->server, 'runtime'))->toBe('* * * * * '.$cron->wrappedCommand());
    $capability->handler()->uninstall($component);
    $this->assertDatabaseMissing('cron_jobs', ['id' => $cron->id]);
});

test('capability changes install and uninstall diffs under the server lock', function () {
    $component = installCapabilityComponent();
    app(InstallComponent::class)->install($component->id);
    $url = '/applications/'.$this->application->id.'/environments/'.$this->environment->id.'/components/'.$component->id.'/capabilities';
    $this->actingAs($this->user)->put($url, ['capabilities' => [['capability' => 'web'], ['capability' => 'worker'], ['capability' => 'scheduler', 'config' => ['command' => 'node tick.mjs']]]])->assertRedirect()->assertSessionHasNoErrors();
    Queue::assertPushed(SyncComponentCapabilitiesJob::class);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'pending', 'needs_redeploy' => true]);
    (new SyncComponentCapabilitiesJob($component->id, $component->server_id))->handle();
    app(CreateWorker::class)->create($this->server, ['name' => 'consumer', 'command' => 'node queue.mjs', 'auto_start' => true, 'auto_restart' => true, 'numprocs' => 1], component: $component->fresh());
    expect($component->workers()->count())->toBe(2);
    $this->put($url, ['capabilities' => []])->assertRedirect()->assertSessionHasNoErrors();
    expect($component->capabilities()->count())->toBe(3);
    (new SyncComponentCapabilitiesJob($component->id, $component->server_id))->handle();
    expect($component->capabilities()->count())->toBe(0)->and($component->workers()->count())->toBe(0)->and($component->cronJobs()->count())->toBe(0);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'ready']);
});

test('capability edits enforce port web and scheduler uniqueness without partial updates', function (string $capability) {
    $first = installCapabilityComponent([['capability' => $capability, 'config' => ['command' => 'node tick.mjs']]]);
    $other = app(CreateComponent::class)->create($this->environment, ['name' => 'Other', 'server_id' => $this->server->id, 'user' => 'another', 'type' => 'app']);
    app(InstallComponent::class)->install($other->id);
    expect(fn () => app(UpdateComponentCapabilities::class)->update($other, ['capabilities' => [['capability' => 'worker'], ['capability' => $capability, 'config' => ['command' => 'node other.mjs']]]]))->toThrow(ValidationException::class);
    expect($other->capabilities()->count())->toBe(0);
    $this->assertDatabaseHas('components', ['id' => $other->id, 'status' => 'ready']);
    expect($first->capabilities()->count())->toBe(1);
})->with(['web', 'scheduler']);

test('proxy port changes are revalidated and mark redeploy', function () {
    $component = installCapabilityComponent([['capability' => 'web', 'config' => ['port' => 14000]]]);
    app(InstallComponent::class)->install($component->id);
    $this->site->update(['port' => 15000]);
    expect(fn () => app(UpdateComponentCapabilities::class)->update($component, ['capabilities' => [['capability' => 'web', 'config' => ['port' => 15000]]]]))->toThrow(ValidationException::class);
    app(UpdateComponentCapabilities::class)->update($component, ['capabilities' => [['capability' => 'web', 'config' => ['port' => 16000, 'start_command' => 'node new.mjs']]]]);
    (new SyncComponentCapabilitiesJob($component->id, $component->server_id))->handle();
    $this->assertDatabaseHas('component_capabilities', ['component_id' => $component->id, 'config->port' => 16000]);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'needs_redeploy' => true]);
    $this->assertDatabaseHas('workers', ['component_id' => $component->id, 'command' => 'node new.mjs']);
});

test('failed capability install and removal retain recoverable rows and retry safely', function (bool $removal) {
    $component = installCapabilityComponent($removal ? [['capability' => 'web']] : []);
    app(InstallComponent::class)->install($component->id);
    app(UpdateComponentCapabilities::class)->update($component, ['capabilities' => $removal ? [] : [['capability' => 'web']]]);
    SSH::fake()->execWillFail();
    $job = new SyncComponentCapabilitiesJob($component->id, $component->server_id);
    expect(fn () => app(SyncComponentCapabilities::class)->handle($component->id))->toThrow(\App\Exceptions\SSHCommandError::class);
    $job->failed(new Exception('SECRET'));
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'installation_failed']);
    $this->assertDatabaseHas('component_capabilities', ['component_id' => $component->id, 'status' => $removal ? 'deletion_failed' : 'installation_failed']);
    expect($component->fresh()->last_error)->not->toContain('SECRET');
    SSH::fake('');
    app(RetryComponentInstall::class)->retry($component);
    app(InstallComponent::class)->install($component->id);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'ready']);
    expect($component->workers()->count())->toBe($removal ? 0 : 1);
})->with([false, true]);

test('runtime changes re-pin components and retain the old checkpoint on failure', function () {
    $component = installCapabilityComponent();
    app(InstallComponent::class)->install($component->id);
    app(UpdateEnvironment::class)->update($this->environment, ['name' => $this->environment->name, 'branch' => 'main', 'runtime_version' => '24']);
    Queue::assertPushed(SyncComponentRuntimeJob::class);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'pending', 'installed_runtime_version' => '22', 'needs_redeploy' => true]);
    SSH::fake()->execWillFail();
    expect(fn () => app(SyncComponentRuntime::class)->handle($component->id))->toThrow(\App\Exceptions\SSHCommandError::class);
    (new SyncComponentRuntimeJob($component->id, $component->server_id))->failed(new Exception('SECRET'));
    $this->assertDatabaseHas('components', ['id' => $component->id, 'installed_runtime_version' => '22', 'status' => 'installation_failed']);
    SSH::fake('');
    app(RetryComponentInstall::class)->retry($component);
    app(InstallComponent::class)->install($component->id);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'installed_runtime_version' => '24', 'status' => 'ready']);
    SSH::assertExecutedContains("mise use --path '".$component->path."/mise.toml' 'node@24'");
});

test('fpm runtime resync provisions new pool before removing unused old pool', function () {
    $this->application->update(['runtime' => 'php']);
    $this->environment->update(['runtime_version' => '8.2']);
    $component = installCapabilityComponent([['capability' => 'web']]);
    app(InstallComponent::class)->install($component->id);
    $this->server->services()->create(['type' => 'php', 'name' => 'php', 'version' => '8.3', 'status' => ServiceStatus::READY]);
    app(UpdateEnvironment::class)->update($this->environment, ['name' => $this->environment->name, 'branch' => 'main', 'runtime_version' => '8.3']);
    $pool = $this->mock(EnsureFpmPool::class);
    $pool->shouldReceive('ensure')->once()->withArgs(fn ($server, $user, $version) => $user === 'runtime' && $version === '8.3')->ordered();
    $cleanup = $this->mock(InstallWebCapability::class);
    $cleanup->shouldReceive('removePool')->once()->withArgs(fn ($owner, $version) => $owner->id === $component->id && $version === '8.2')->andThrow(new RuntimeException('Cleanup failed'));
    expect(fn () => app(SyncComponentRuntime::class)->handle($component->id))->toThrow(RuntimeException::class);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'installed_runtime_version' => '8.2']);
    app()->forgetInstance(EnsureFpmPool::class);
    app()->forgetInstance(InstallWebCapability::class);
    (new SyncComponentRuntimeJob($component->id, $component->server_id))->handle();
    $this->assertDatabaseHas('components', ['id' => $component->id, 'installed_runtime_version' => '8.3', 'status' => 'ready']);
    SSH::assertExecutedContains('/etc/php/8.2/fpm/pool.d/runtime.conf');
});

test('runtime change rejects missing services and nonready components atomically', function (bool $nonready) {
    $this->application->update(['runtime' => 'php']);
    $this->environment->update(['runtime_version' => '8.2']);
    $component = installCapabilityComponent([['capability' => 'web']]);
    if (! $nonready) {
        app(InstallComponent::class)->install($component->id);
    }
    expect(fn () => app(UpdateEnvironment::class)->update($this->environment, ['name' => 'Changed', 'branch' => 'main', 'runtime_version' => '8.3']))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'name' => $this->environment->name, 'runtime_version' => '8.2']);
    Queue::assertNotPushed(SyncComponentRuntimeJob::class);
})->with([false, true]);

test('capability and runtime jobs respect the shared server mutex and ignore deleted components', function (string $jobClass) {
    $component = installCapabilityComponent();
    $lock = Cache::lock('unique-queue:server-'.$this->server->id, 60);
    $lock->get();
    try {
        $job = (new $jobClass($component->id, $this->server->id))->withFakeQueueInteractions();
        $job->handle();
        $job->assertReleased(30);
        SSH::assertNotExecutedContains('');
    } finally {
        $lock->release();
    }
    $component->delete();
    (new $jobClass($component->id, $this->server->id))->handle();
    SSH::assertNotExecutedContains('');
})->with([SyncComponentCapabilitiesJob::class, SyncComponentRuntimeJob::class]);

test('capability and runtime jobs do nothing once a parent begins teardown', function (string $jobClass, string $parent) {
    $component = installCapabilityComponent([['capability' => 'web']]);
    if ($parent === 'application') {
        $this->application->status = \App\Enums\ApplicationStatus::DELETING;
        $this->application->save();
    } else {
        $this->environment->status = \App\Enums\EnvironmentStatus::DELETING;
        $this->environment->save();
    }
    (new $jobClass($component->id, $component->server_id))->handle();
    SSH::assertNotExecutedContains('');
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'pending']);
})->with([SyncComponentCapabilitiesJob::class, SyncComponentRuntimeJob::class])->with(['application', 'environment']);

test('proxy capability name collisions are rejected before entering installation', function () {
    $component = installCapabilityComponent([['capability' => 'worker']]);
    app(InstallComponent::class)->install($component->id);
    app(CreateWorker::class)->create($this->server, ['name' => 'app', 'command' => './consumer', 'auto_start' => true, 'auto_restart' => true, 'numprocs' => 1], component: $component->fresh());

    expect(fn () => app(UpdateComponentCapabilities::class)->update($component, ['capabilities' => [['capability' => 'worker'], ['capability' => 'web']]]))
        ->toThrow(ValidationException::class, 'Rename the worker named "app"');
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'ready']);
    $this->assertDatabaseMissing('component_capabilities', ['component_id' => $component->id, 'capability' => 'web']);
    Queue::assertNotPushed(SyncComponentCapabilitiesJob::class);
});

test('scheduler command retries reconcile the old remote line without importing an unmanaged copy', function () {
    $component = installCapabilityComponent([['capability' => 'scheduler', 'config' => ['command' => 'node old.mjs']]]);
    app(InstallComponent::class)->install($component->id);
    $oldCrontab = CronJob::crontab($this->server, $component->user);
    $capability = $component->capabilities()->sole();
    $capability->config = ['command' => 'node new.mjs'];
    $capability->save();
    $ssh = Mockery::mock(\App\Support\Testing\SSHFake::class, [$oldCrontab])->makePartial();
    SSH::swap($ssh);
    $ssh->shouldReceive('exec')->withArgs(fn ($command, $log = '') => $log === 'update-cron')->once()->andThrow(new \App\Exceptions\SSHCommandError);

    expect(fn () => $capability->handler()->install($component))->toThrow(\App\Exceptions\SSHCommandError::class);
    $this->assertDatabaseHas('cron_jobs', ['component_id' => $component->id, 'command' => 'node old.mjs', 'status' => 'ready']);
    SSH::fake($oldCrontab);
    $capability->handler()->install($component);
    expect($this->server->cronJobs()->count())->toBe(1);
    $this->assertDatabaseHas('cron_jobs', ['component_id' => $component->id, 'command' => 'node new.mjs']);
    $capability->handler()->uninstall($component);
    expect($this->server->cronJobs()->count())->toBe(0)->and(CronJob::crontab($this->server, $component->user))->toBe('');
});

test('runtime checkpoint protects the previous service until resync finishes', function (bool $web) {
    $this->application->update(['runtime' => 'php']);
    $this->environment->update(['runtime_version' => '8.2']);
    $component = installCapabilityComponent($web ? [['capability' => 'web']] : []);
    app(InstallComponent::class)->install($component->id);
    $this->site->update(['php_version' => '8.3']);
    $this->server->services()->create(['type' => 'php', 'name' => 'php', 'version' => '8.3', 'status' => ServiceStatus::READY]);
    app(UpdateEnvironment::class)->update($this->environment, ['name' => $this->environment->name, 'branch' => 'main', 'runtime_version' => '8.3']);
    $service = $this->server->php('8.2');

    expect($this->server->componentsUsing($service)->modelKeys())->toBe([$component->id]);
    expect(fn () => \Illuminate\Support\Facades\Validator::make(['service' => $service->id], $service->handler()->deletionRules())->validate())->toThrow(ValidationException::class);
    app(SyncComponentRuntime::class)->handle($component->id);
    expect($this->server->componentsUsing($service))->toBeEmpty();
    \Illuminate\Support\Facades\Validator::make(['service' => $service->id], $service->handler()->deletionRules())->validate();
})->with([false, true]);

test('fpm retries activate an already written pool before advancing the runtime checkpoint', function () {
    $this->application->update(['runtime' => 'php']);
    $this->environment->update(['runtime_version' => '8.2']);
    $component = installCapabilityComponent([['capability' => 'web']]);
    app(InstallComponent::class)->install($component->id);
    $this->server->services()->create(['type' => 'php', 'name' => 'php', 'version' => '8.3', 'status' => ServiceStatus::READY]);
    app(UpdateEnvironment::class)->update($this->environment, ['name' => $this->environment->name, 'branch' => 'main', 'runtime_version' => '8.3']);
    $ssh = Mockery::mock(\App\Support\Testing\SSHFake::class, [''])->makePartial();
    SSH::swap($ssh);
    $attempts = 0;
    $active = false;
    $ssh->shouldReceive('exec')->withArgs(fn ($command) => str_contains((string) $command, 'test -f /etc/php/8.3/fpm/pool.d/runtime.conf'))
        ->twice()->andReturnUsing(function ($command) use (&$attempts, &$active): string {
            if (! $active && ($attempts === 0 || str_contains((string) $command, 'test -S /run/php/php8.3-fpm-runtime.sock'))) {
                throw new \App\Exceptions\SSHCommandError;
            }

            return '';
        });
    $ssh->shouldReceive('exec')->withArgs(fn ($command) => str_contains((string) $command, 'restart php8.3-fpm'))
        ->twice()->andReturnUsing(function () use (&$attempts, &$active): string {
            if ($attempts++ === 0) {
                throw new \App\Exceptions\SSHCommandError;
            }
            $active = true;

            return '';
        });
    $this->mock(InstallWebCapability::class)->shouldReceive('removePool')->once()->andReturnUsing(function () use (&$active): void {
        expect($active)->toBeTrue();
    });

    expect(fn () => app(SyncComponentRuntime::class)->handle($component->id))->toThrow(\App\Exceptions\SSHCommandError::class);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'installed_runtime_version' => '8.2', 'status' => 'pending']);
    app(SyncComponentRuntime::class)->handle($component->id);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'installed_runtime_version' => '8.3', 'status' => 'ready']);
});

test('pending configuration events are emitted only after the mutation commits', function (string $kind, bool $rollback) {
    $component = installCapabilityComponent();
    app(InstallComponent::class)->install($component->id);
    Event::fake([SocketEvent::class]);
    try {
        \Illuminate\Support\Facades\DB::transaction(function () use ($component, $kind, $rollback): void {
            if ($kind === 'capability') {
                app(UpdateComponentCapabilities::class)->update($component, ['capabilities' => [['capability' => 'worker']]]);
            } else {
                app(UpdateEnvironment::class)->update($this->environment, ['name' => $this->environment->name, 'branch' => 'main', 'runtime_version' => '24']);
            }
            Event::assertNotDispatched(SocketEvent::class);
            if ($rollback) {
                throw new RuntimeException('Rollback');
            }
        });
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Rollback');
    }
    if ($rollback) {
        Event::assertNotDispatched(SocketEvent::class);
        $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'ready']);
    } else {
        Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'component.updated' && $event->data->data['status'] === 'pending');
    }
})->with(['capability', 'runtime'])->with([false, true]);

test('background scheduler operations requeue on cron contention and finish without manual retry', function (string $operation) {
    $component = installCapabilityComponent([['capability' => 'scheduler', 'config' => ['command' => './tick']]]);
    if ($operation !== 'install') {
        app(InstallComponent::class)->install($component->id);
        if ($operation === 'delete') {
            app(DeleteComponent::class)->delete($component, []);
        } else {
            app(UpdateComponentCapabilities::class)->update($component, ['capabilities' => $operation === 'remove' ? [] : [['capability' => 'scheduler', 'config' => ['command' => './new-tick']]]]);
        }
    }
    $jobClass = match ($operation) {
        'install' => \App\Jobs\Component\InstallComponentJob::class,
        'delete' => \App\Jobs\Component\DeleteComponentJob::class,
        default => SyncComponentCapabilitiesJob::class,
    };
    $lock = Cache::lock('cron-jobs:server-'.$this->server->id, 60);
    $lock->get();
    SSH::fake('');
    try {
        $job = (new $jobClass($component->id, $this->server->id))->withFakeQueueInteractions();
        $job->handle();
        $job->assertReleased(30)->assertNotFailed();
        expect($component->fresh()->status)->not->toBeIn([ComponentStatus::INSTALLATION_FAILED, ComponentStatus::DELETION_FAILED]);
        expect($component->capabilities()->whereIn('status', [ComponentStatus::INSTALLATION_FAILED, ComponentStatus::DELETION_FAILED])->exists())->toBeFalse();
        SSH::assertNotExecutedContains('crontab -');
        expect(Cache::lock('unique-queue:server-'.$this->server->id, 60)->get(fn () => true))->toBeTrue();
    } finally {
        $lock->release();
    }
    $retry = (new $jobClass($component->id, $this->server->id))->withFakeQueueInteractions();
    $retry->handle();
    $retry->assertNotReleased()->assertNotFailed();
    if ($operation === 'delete') {
        $this->assertDatabaseMissing('components', ['id' => $component->id]);
    } else {
        $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'ready']);
    }
    expect($this->server->cronJobs()->count())->toBe(in_array($operation, ['remove', 'delete']) ? 0 : 1);
})->with(['install', 'update', 'remove', 'delete']);
