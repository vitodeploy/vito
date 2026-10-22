<?php

use App\Actions\Component\HasComponentRelease;
use App\Actions\Component\RestartComponentWorkers;
use App\Actions\CronJob\CreateCronJob;
use App\Actions\CronJob\DeleteCronJob;
use App\Actions\CronJob\DisableCronJob;
use App\Actions\CronJob\EditCronJob;
use App\Actions\CronJob\EnableCronJob;
use App\Actions\CronJob\SyncCronJobs;
use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\DeleteWorker;
use App\Actions\Worker\EditWorker;
use App\Actions\Worker\InstallWorker;
use App\Actions\Worker\ManageWorker;
use App\Actions\Worker\RestartAllWorkers;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Enums\UserRole;
use App\Enums\WorkerStatus;
use App\Events\SocketEvent;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Helpers\ComponentShell;
use App\Http\Resources\CronJobResource;
use App\Http\Resources\WorkerResource;
use App\Jobs\Worker\CreateJob;
use App\Jobs\Worker\EditJob;
use App\Jobs\Worker\ManageJob;
use App\Jobs\Worker\RestartAllJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\CronJob;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\IsolatedUser;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id]);
    $this->environment = Environment::factory()->for($this->application)->create(['runtime_version' => '22']);
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    foreach (['worker', 'scheduler'] as $capability) {
        ComponentCapability::factory()->for($this->component)->create(['capability' => $capability, 'config' => ['command' => 'node schedule.mjs'], 'status' => ComponentStatus::READY]);
    }
    $this->url = '/applications/'.$this->application->id.'/environments/'.$this->environment->id.'/components/'.$this->component->id;
    $this->workerInput = ['name' => 'queue', 'command' => 'node queue.mjs', 'auto_start' => true, 'auto_restart' => true, 'numprocs' => 1];
    $this->cronInput = ['name' => 'daily', 'command' => 'node daily.mjs', 'frequency' => '0 0 * * *'];
});

function componentProcessScript(string $wrapped): string
{
    preg_match('/echo ([A-Za-z0-9+\/=]+) \| base64 -d/', $wrapped, $matches);

    return base64_decode($matches[1]);
}

test('component shell quotes commands and paths without interpolating variables into config', function () {
    $this->component->path = "/tmp/component's space";
    $command = "printf '%s\\n' \"\$PORT\"; printf '%s' 'a;b'";
    $wrapped = ComponentShell::wrap($this->component, $command);
    $script = componentProcessScript($wrapped);
    expect($wrapped)->not->toContain("\n", '%', ';')
        ->and($script)->toContain('/home/'.$this->component->user.'/.local/share/mise/shims', '[ -d '.escapeshellarg($this->component->currentPath()).' ] || exit 0;', 'cd -- '.escapeshellarg($this->component->currentPath()).' || exit 1;', 'exec '.escapeshellarg($this->component->basePath().'/vito-env-exec').' '.escapeshellarg($this->component->envPath()).' /bin/bash -p -c '.escapeshellarg($command))
        ->not->toContain('source ', 'set -a');
    $explicit = componentProcessScript(ComponentShell::wrap($this->component, $command, '/tmp/env', '/tmp/staged release'));
    expect($explicit)->toContain("cd -- '/tmp/staged release' || exit 1;", "'/tmp/env' /bin/bash -p -c ")->not->toContain('exit 0');
});

test('php component commands prefer component executables and Composer over server defaults', function (bool $staged) {
    $this->application->update(['runtime' => 'laravel']);
    $this->environment->update(['runtime_version' => '8.4']);
    $this->component->refresh();
    $this->component->path = "/home/runtime/component's directory";
    $command = 'composer install && php artisan schedule:run';
    $script = componentProcessScript(ComponentShell::wrap(
        $this->component, $command, $staged ? '/tmp/staged.env' : null, $staged ? '/tmp/release' : null,
    ));

    expect($script)->toContain('export PATH='.escapeshellarg($this->component->basePath().'/bin:'.$this->component->basePath().'/.local/vito/bin:/usr/local/bin:/usr/bin:/bin:/home/'.$this->component->user.'/.local/bin').'; ')
        ->toContain('/bin/bash -p -c '.escapeshellarg($command));
})->with([false, true]);

test('component commands retain optional tooling installed for their shared linux user', function () {
    $isolatedUser = IsolatedUser::factory()->create(['server_id' => $this->server->id, 'username' => $this->component->user]);
    $isolatedUser->setToolingVersion('composer', '2');
    $this->component->isolated_user_id = $isolatedUser->id;
    $this->component->save();

    $script = componentProcessScript(ComponentShell::wrap($this->component->fresh(), 'composer --version'));

    expect($script)->toContain('/home/'.$this->component->user.'/.local/vito/bin')
        ->not->toContain($this->component->basePath().'/.local/vito/bin');
});

test('manual component process mutations reject pending and running deployments', function (string $operation, EnvironmentDeploymentStatus $status) {
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    EnvironmentDeployment::factory()->for($this->environment)->create(['status' => $status]);
    $mutation = match ($operation) {
        'create' => fn () => app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component),
        'edit' => fn () => app(EditWorker::class)->edit($worker, $this->workerInput),
        'delete' => fn () => app(DeleteWorker::class)->delete($worker),
        'stop' => fn () => app(ManageWorker::class)->stop($worker),
        'cron' => fn () => app(CreateCronJob::class)->create($this->server, $this->cronInput, component: $this->component),
    };
    expect($mutation)->toThrow(ValidationException::class);
    expect(SSH::getExecutedCommands())->toBeEmpty();
    $this->assertDatabaseHas('workers', ['id' => $worker->id]);
})->with(['create', 'edit', 'delete', 'stop', 'cron'])->with([EnvironmentDeploymentStatus::PENDING, EnvironmentDeploymentStatus::RUNNING]);

test('queued worker mutations wait for active deployment completion before touching processes', function (string $operation) {
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    $run = EnvironmentDeployment::factory()->for($this->environment)->create(['status' => EnvironmentDeploymentStatus::RUNNING]);
    $makeJob = fn () => match ($operation) {
        'create' => new CreateJob($worker),
        'edit' => new EditJob($worker),
        'stop' => new ManageJob($worker, 'stop', WorkerStatus::STOPPED),
    };
    $job = $makeJob()->withFakeQueueInteractions();
    $job->handle();
    $job->assertReleased(30);
    expect(SSH::getExecutedCommands())->toBeEmpty();
    $run->status = EnvironmentDeploymentStatus::FINISHED;
    $run->save();
    $job = $makeJob()->withFakeQueueInteractions();
    $job->handle();
    $job->assertNotReleased();
    $job->assertNotFailed();
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'status' => WorkerStatus::STOPPED]);
    SSH::assertExecutedContains('supervisorctl');
})->with(['create', 'edit', 'stop']);

test('worker operations share the deployment component mutex', function () {
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    $lock = Cache::lock('unique-queue:component-'.$this->component->id, 60);
    $lock->get();
    try {
        expect(fn () => app(DeleteWorker::class)->delete($worker))->toThrow(ValidationException::class);
        $job = (new CreateJob($worker))->withFakeQueueInteractions();
        $job->handle();
        $job->assertReleased(30);
        expect(SSH::getExecutedCommands())->toBeEmpty();
    } finally {
        $lock->release();
    }
});

test('component workers are installed stopped with a wrapped command and no per-worker environment', function () {
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    $worker->environment = [['key' => 'SECRET', 'value' => 'never-in-process-config', 'is_secret' => true]];
    $worker->save();
    (new CreateJob($worker))->handle();

    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'component_id' => $this->component->id, 'site_id' => null, 'user' => $this->component->user, 'status' => 'stopped', 'auto_start' => true]);
    SSH::assertExecutedContains(" > '/etc/supervisor/conf.d/".$worker->id.".conf'");
    expect(SSH::getUploadedContent())->toContain('command='.ComponentShell::wrap($this->component, $worker->command), 'autostart=false')
        ->not->toContain('environment=', 'never-in-process-config', 'directory=');
    SSH::assertNotExecutedContains('supervisorctl start');
});

test('component worker CRUD reuses the scoped actions and fixes its owner', function () {
    $this->actingAs($this->user)->post($this->url.'/workers', $this->workerInput)->assertRedirect()->assertSessionHasNoErrors();
    $worker = $this->component->workers()->sole();
    Queue::assertPushed(CreateJob::class);
    $this->actingAs($this->user)->put($this->url.'/workers/'.$worker->id, array_replace($this->workerInput, ['command' => 'node changed.mjs', 'auto_start' => false]))->assertRedirect()->assertSessionHasNoErrors();
    Queue::assertPushed(EditJob::class);
    (new EditJob($worker->fresh()))->handle();
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'component_id' => $this->component->id, 'user' => $this->component->user, 'command' => 'node changed.mjs', 'auto_start' => false, 'status' => 'stopped']);
    $this->delete($this->url.'/workers/'.$worker->id)->assertRedirect();
    $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
    SSH::assertExecutedContains('rm -rf /etc/supervisor/conf.d/'.$worker->id.'.conf');
});

test('component cron CRUD uses its wrapper and never imports the managed line as a server job', function () {
    $this->actingAs($this->user)->post($this->url.'/cronjobs', $this->cronInput)->assertRedirect()->assertSessionHasNoErrors();
    $cron = $this->component->cronJobs()->sole();
    $this->assertDatabaseHas('cron_jobs', ['id' => $cron->id, 'component_id' => $this->component->id, 'site_id' => null, 'user' => $this->component->user, 'status' => 'ready']);
    $crontab = CronJob::crontab($this->server, $this->component->user);
    expect($crontab)->toBe('0 0 * * * '.$cron->wrappedCommand());
    SSH::fake($crontab);
    app(SyncCronJobs::class)->sync($this->server);
    expect($this->server->cronJobs()->where('user', $this->component->user)->count())->toBe(1);
    SSH::fake('');
    $this->put($this->url.'/cronjobs/'.$cron->id, array_replace($this->cronInput, ['command' => 'node changed.mjs', 'frequency' => 'custom', 'custom' => '*/5 * * * *']))->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('cron_jobs', ['id' => $cron->id, 'command' => 'node changed.mjs', 'frequency' => '*/5 * * * *']);
    $this->post($this->url.'/cronjobs/'.$cron->id.'/disable')->assertRedirect();
    $this->assertDatabaseHas('cron_jobs', ['id' => $cron->id, 'status' => 'disabled']);
    $this->post($this->url.'/cronjobs/'.$cron->id.'/enable')->assertRedirect();
    $this->assertDatabaseHas('cron_jobs', ['id' => $cron->id, 'status' => 'ready']);
    $this->delete($this->url.'/cronjobs/'.$cron->id)->assertRedirect();
    $this->assertDatabaseMissing('cron_jobs', ['id' => $cron->id]);
});

test('component and server process forms reject attempts to replace ownership execution user or variables', function (string $kind, string $field, mixed $value, bool $serverPage) {
    $input = $kind === 'workers' ? $this->workerInput : $this->cronInput;
    $process = $kind === 'workers'
        ? app(CreateWorker::class)->create($this->server, $input, component: $this->component)
        : app(CreateCronJob::class)->create($this->server, $input, component: $this->component);
    $base = $serverPage ? '/servers/'.$this->server->id : $this->url;
    $this->actingAs($this->user)->putJson($base.'/'.$kind.'/'.$process->id, [...$input, $field => $value])->assertUnprocessable()->assertJsonValidationErrors($field);
})->with(['workers', 'cronjobs'])->with([
    ['user', 'root'], ['site_id', 1], ['component_id', 1], ['component_capability_id', 1], ['directory', '/tmp'], ['environment', [['key' => 'SECRET', 'value' => 'unsafe']]],
])->with([false, true]);

test('component workers reject per-worker environment updates on the server endpoint', function () {
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    $this->actingAs($this->user)->patchJson('/servers/'.$this->server->id.'/workers/'.$worker->id.'/env', ['variables' => []])->assertUnprocessable()->assertJsonValidationErrors('variables');
});

test('managed processes cannot be changed from component or server endpoints', function (string $kind, string $method, string $suffix, bool $serverPage) {
    $capability = $this->component->capabilities()->where('capability', $kind === 'workers' ? 'worker' : 'scheduler')->sole();
    $process = $kind === 'workers'
        ? app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component, managedBy: $capability)
        : app(CreateCronJob::class)->create($this->server, $this->cronInput, component: $this->component, managedBy: $capability);
    $base = $serverPage ? '/servers/'.$this->server->id : $this->url;
    $url = $serverPage && $kind === 'workers' && $method === 'DELETE' ? $base.'/'.$process->id : $base.'/'.$kind.'/'.$process->id.$suffix;
    $this->actingAs($this->user)->json($method, $url, $kind === 'workers' ? $this->workerInput : $this->cronInput)->assertForbidden();
})->with([
    ['workers', 'PUT', ''], ['workers', 'DELETE', ''], ['workers', 'POST', '/start'], ['workers', 'POST', '/stop'], ['workers', 'POST', '/restart'],
    ['cronjobs', 'PUT', ''], ['cronjobs', 'DELETE', ''], ['cronjobs', 'POST', '/enable'], ['cronjobs', 'POST', '/disable'],
])->with([false, true]);

test('component process routes require the complete owner chain and project access', function (string $kind) {
    $input = $kind === 'workers' ? $this->workerInput : $this->cronInput;
    $other = Component::factory()->for($this->environment)->for($this->server)->create(['user' => 'other']);
    $process = $kind === 'workers' ? Worker::factory()->for($other)->for($this->server)->create() : CronJob::factory()->for($other)->for($this->server)->create();
    $this->actingAs($this->user)->putJson($this->url.'/'.$kind.'/'.$process->id, $input)->assertNotFound();
    $foreign = Application::factory()->create();
    $this->postJson(str_replace('/applications/'.$this->application->id, '/applications/'.$foreign->id, $this->url).'/'.$kind, $input)->assertNotFound();
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->postJson($this->url.'/'.$kind, $input)->assertForbidden();
})->with(['workers', 'cronjobs']);

test('never-deployed workers cannot start manually or through a queued management job', function (string $action) {
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    $this->actingAs($this->user)->postJson($this->url.'/workers/'.$worker->id.'/'.$action)->assertUnprocessable()->assertJsonValidationErrors('name');
    $job = (new ManageJob($worker, $action, WorkerStatus::RUNNING))->withFakeQueueInteractions();
    $job->handle();
    $job->assertFailedWith(ValidationException::class);
    SSH::assertNotExecutedContains('supervisorctl start');
    SSH::assertNotExecutedContains('supervisorctl restart');
})->with(['start', 'restart']);

test('deployed component workers can be managed and restarted through the shared restart core', function () {
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    SSH::fake('VITO_COMPONENT_CURRENT_EXISTS');
    app(InstallWorker::class)->install($worker);
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'status' => 'running']);
    $this->actingAs($this->user)->post($this->url.'/workers/'.$worker->id.'/stop')->assertRedirect()->assertSessionHasNoErrors();
    (new ManageJob($worker->fresh(), 'stop', WorkerStatus::STOPPED))->handle();
    $this->post($this->url.'/workers/'.$worker->id.'/start')->assertRedirect()->assertSessionHasNoErrors();
    (new ManageJob($worker->fresh(), 'start', WorkerStatus::RUNNING))->handle();
    $this->post($this->url.'/workers/'.$worker->id.'/restart')->assertRedirect()->assertSessionHasNoErrors();
    (new ManageJob($worker->fresh(), 'restart', WorkerStatus::RUNNING))->handle();
    $this->mock(HasComponentRelease::class)->shouldReceive('check')->andReturn(true);
    SSH::fake("{$worker->id}:{$worker->id}_00: started");
    app(RestartComponentWorkers::class)->restart($this->component);
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'status' => 'running', 'error' => null]);
});

test('server restart all and release restart skip undeployed component processes', function () {
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    app(InstallWorker::class)->install($worker);
    SSH::fake('');
    app(RestartAllWorkers::class)->restart($this->server);
    (new RestartAllJob($this->server))->handle();
    app(RestartComponentWorkers::class)->restart($this->component);
    SSH::assertNotExecutedContains('supervisorctl restart');
    SSH::assertNotExecutedContains('supervisorctl start');
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'status' => 'stopped']);
});

test('server process lists expose component owners alongside site owners', function (string $kind) {
    if ($kind === 'workers') {
        app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
        Worker::factory()->for($this->server)->for($this->site)->create();
    } else {
        app(CreateCronJob::class)->create($this->server, $this->cronInput, component: $this->component);
        CronJob::factory()->for($this->server)->for($this->site)->create(['hidden' => false]);
    }
    $response = $this->actingAs($this->user)->get(route($kind, $this->server))->assertOk();
    $rows = collect($response->viewData('page')['props'][$kind]['data']);
    $owner = $rows->firstWhere('component_id', $this->component->id);
    expect($owner['component_name'])->toBe($this->component->name)
        ->and($owner['component_url'])->toBe($this->url)
        ->and($owner['site_id'])->toBeNull()
        ->and($rows->firstWhere('site_id', $this->site->id)['component_id'])->toBeNull();
})->with(['workers', 'cronjobs']);

test('component surfaces return safe backend tables and locked process metadata', function () {
    app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    app(CreateCronJob::class)->create($this->server, $this->cronInput, component: $this->component);
    $this->actingAs($this->user)->get($this->url)->assertRedirect(route('environments.show', [$this->application, $this->environment, 'component' => $this->component->id]));
    $this->get(route('environments.show', [$this->application, $this->environment, 'component' => $this->component->id]))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('applications/environments/show')
        ->where('component.surfaces', fn ($surfaces) => $surfaces->sort()->values()->all() === ['cron_jobs', 'workers'])
        ->has('workers.data', 1)->has('cronjobs.data', 1)
        ->where('workers.data.0.managed', 'No')->where('cronjobs.data.0.managed', 'No')
        ->where('workers.data.0.worker.component_id', $this->component->id)
        ->where('workers.data.0.worker.component_url', $this->url)
        ->missing('workers.data.0.worker.environment')
        ->missing('component.installed_runtime_version'));
});

test('component process mutations refuse a held server lock without changing rows', function (string $kind, string $method) {
    $process = $kind === 'workers'
        ? app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component)
        : app(CreateCronJob::class)->create($this->server, $this->cronInput, component: $this->component);
    $lock = Cache::lock('unique-queue:server-'.$this->server->id, 60);
    $lock->get();
    SSH::fake('');
    try {
        $url = $this->url.'/'.$kind.($method === 'POST' ? '' : '/'.$process->id);
        $this->actingAs($this->user)->json($method, $url, $kind === 'workers' ? $this->workerInput : $this->cronInput)
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertDatabaseHas($process->getTable(), ['id' => $process->id, 'command' => $process->command]);
        expect(SSH::getExecutedCommands())->toBeEmpty();
    } finally {
        $lock->release();
    }
})->with(['workers', 'cronjobs'])->with(['POST', 'PUT', 'DELETE']);

test('component cron mutations roll back when the remote write fails', function (string $action) {
    $cron = app(CreateCronJob::class)->create($this->server, $this->cronInput, component: $this->component);
    $this->partialMock(SyncCronJobs::class)->shouldReceive('sync')->andReturnNull();
    SSH::fake()->execWillFail();
    $mutation = match ($action) {
        'create' => fn () => app(CreateCronJob::class)->create($this->server, [...$this->cronInput, 'name' => 'other'], component: $this->component),
        'edit' => fn () => app(EditCronJob::class)->edit($this->server, $cron, [...$this->cronInput, 'command' => 'changed']),
        'delete' => fn () => app(DeleteCronJob::class)->delete($this->server, $cron),
        'disable' => fn () => app(DisableCronJob::class)->disable($this->server, $cron),
    };
    expect($mutation)->toThrow(SSHCommandError::class);
    expect($this->component->cronJobs()->count())->toBe(1);
    $this->assertDatabaseHas('cron_jobs', ['id' => $cron->id, 'command' => 'node daily.mjs', 'status' => 'ready']);
})->with(['create', 'edit', 'delete', 'disable']);

test('queued component worker writes stop when teardown begins and do not expose errors', function (string $jobClass) {
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    $this->component->status = ComponentStatus::DELETING;
    $this->component->save();
    $job = (new $jobClass($worker))->withFakeQueueInteractions();
    $job->handle();
    $job->assertFailedWith(ValidationException::class);
    SSH::assertNotExecutedContains('supervisorctl');
    $job->failed(new RuntimeException('sensitive exception contents'));
    expect($worker->fresh()->error)->not->toContain('sensitive exception contents');
})->with([CreateJob::class, EditJob::class]);

test('component release probes preserve shell quoting and component removal is retryable when stopped', function () {
    $this->component->path = "/tmp/component's space";
    $probe = view('ssh.components.has-current', ['component' => $this->component])->render();
    expect($probe)->toContain('[ -d '.escapeshellarg($this->component->currentPath()).' ]')->not->toContain('&#039;');
    $worker = app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component);
    app(DeleteWorker::class)->delete($worker);
    SSH::assertNotExecutedContains('supervisorctl stop');
    SSH::assertExecutedContains('supervisorctl update');
});

test('component process metadata does not expand the public API response', function (string $kind) {
    $resource = $kind === 'worker'
        ? new WorkerResource(app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component))
        : new CronJobResource(app(CreateCronJob::class)->create($this->server, $this->cronInput, component: $this->component));
    expect($resource->resolve(Request::create('/api/servers/1/workers')))->not->toHaveKeys(['component_id', 'managed', 'component_url', 'component_name']);
})->with(['worker', 'cronjob']);

test('cron sync identifies component entries independently of mutable command context', function (string $change) {
    $cron = app(CreateCronJob::class)->create($this->server, $this->cronInput, component: $this->component);
    $remote = CronJob::crontab($this->server, $this->component->user);
    if ($change === 'command') {
        $cron->update(['command' => './changed']);
    } elseif ($change === 'context') {
        $this->application->update(['runtime' => 'static']);
    } else {
        $cron->delete();
    }
    SSH::fake($remote);
    app(SyncCronJobs::class)->sync($this->server);

    expect($this->server->cronJobs()->whereNull('component_id')->count())->toBe(0);
    expect($this->component->cronJobs()->count())->toBe($change === 'deleted' ? 0 : 1);
})->with(['command', 'context', 'deleted']);

test('all user cron mutation entrypoints share the crontab mutex', function (string $owner, string $action) {
    $component = $owner === 'component' ? $this->component : null;
    $site = $owner === 'site' ? $this->site : null;
    $input = [...$this->cronInput, 'user' => $component?->user ?? $this->server->getSshUser()];
    $cron = app(CreateCronJob::class)->create($this->server, $input, $site, $component);
    $mutation = match ($action) {
        'create' => fn () => app(CreateCronJob::class)->create($this->server, $input, $site, $component),
        'edit' => fn () => app(EditCronJob::class)->edit($this->server, $cron, $input, $site, $component),
        'delete' => fn () => app(DeleteCronJob::class)->delete($this->server, $cron, $component),
        'enable' => fn () => app(EnableCronJob::class)->enable($this->server, $cron),
        'disable' => fn () => app(DisableCronJob::class)->disable($this->server, $cron),
        'sync' => fn () => app(SyncCronJobs::class)->sync($this->server),
    };
    $lock = Cache::lock('cron-jobs:server-'.$this->server->id, 60);
    $lock->get();
    SSH::fake('');
    try {
        expect($mutation)->toThrow(ValidationException::class);
        expect(SSH::getExecutedCommands())->toBeEmpty();
        $this->assertDatabaseHas('cron_jobs', ['id' => $cron->id, 'status' => 'ready']);
    } finally {
        $lock->release();
    }
})->with(['component', 'site', 'server'])->with(['create', 'edit', 'delete', 'enable', 'disable', 'sync']);

test('managed process creation requires its capability component', function (string $kind, bool $missing) {
    $capability = $this->component->capabilities()->where('capability', $kind === 'worker' ? 'worker' : 'scheduler')->sole();
    $component = $missing ? null : Component::factory()->for($this->environment)->for($this->server)->create(['user' => 'other']);
    $action = $kind === 'worker' ? CreateWorker::class : CreateCronJob::class;
    expect(fn () => app($action)->create($this->server, $kind === 'worker' ? $this->workerInput : $this->cronInput, component: $component, managedBy: $capability))
        ->toThrow(ValidationException::class);
    expect(SSH::getExecutedCommands())->toBeEmpty();
    $this->assertDatabaseMissing($kind === 'worker' ? 'workers' : 'cron_jobs', ['component_capability_id' => $capability->id]);
})->with(['worker', 'cron'])->with([false, true]);

test('component process ownership cannot be mass assigned', function (string $model) {
    $process = new $model(['component_id' => $this->component->id, 'component_capability_id' => 1]);
    expect($process->component_id)->toBeNull()->and($process->component_capability_id)->toBeNull();
})->with([Worker::class, CronJob::class]);

test('component restart handles an absent process manager without dereferencing null', function (bool $hasWorkers) {
    $worker = $hasWorkers ? app(CreateWorker::class)->create($this->server, $this->workerInput, component: $this->component) : null;
    $this->server->services()->where('type', 'process_manager')->delete();
    $this->mock(HasComponentRelease::class)->shouldReceive('check')->andReturn(true);
    if ($hasWorkers) {
        expect(fn () => app(RestartComponentWorkers::class)->restart($this->component))->toThrow(RuntimeException::class);
    } else {
        app(RestartComponentWorkers::class)->restart($this->component);
    }
    expect(SSH::getExecutedCommands())->toBeEmpty();
    if ($worker) {
        $this->assertDatabaseHas('workers', ['id' => $worker->id, 'status' => 'failed']);
    }
})->with([false, true]);
