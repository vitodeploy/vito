<?php

use App\Actions\Worker\ManageWorker;
use App\Enums\UserRole;
use App\Enums\WorkerStatus;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Jobs\Worker\ManageJob;
use App\Jobs\Worker\RestartAllJob;
use App\Models\Component;
use App\Models\Server;
use App\Models\Site;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see workers', function () {
    $this->actingAs($this->user);

    Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);

    $this->get(route('workers', [
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('workers/index'));
});

test('delete worker', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);

    $this->delete(route('workers.destroy', [
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('workers', [
        'id' => $worker->id,
    ]);

    SSH::assertExecutedContains('sudo sh -c '.escapeshellarg('rm -f -- '.escapeshellarg($worker->getLogFile()).' '.escapeshellarg($worker->getLogFile()).'.*'));
    SSH::assertNotExecutedContains('/var/log/vito/workers/*/');
});

test('create worker', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('workers.store', [
        'server' => $this->server,
    ]), [
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('workers', [
        'server_id' => $this->server->id,
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
        'status' => WorkerStatus::RUNNING,
    ]);
});

test('create worker as isolated user', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->site->user = 'example';
    $this->site->save();

    $this->post(route('workers.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'example',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('workers', [
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'command' => 'php artisan worker:work',
        'user' => 'example',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
        'status' => WorkerStatus::RUNNING,
    ]);

    $worker = $this->server->workers()->sole();
    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'stdout_logfile' => '/var/log/vito/workers/example/'.$worker->id.'.log',
    ]);
    expect(SSH::getUploadedContent())->toContain('stdout_logfile='.$worker->stdout_logfile);
    SSH::assertExecutedContains("install -d -o root -g 'example' -m 750 '/var/log/vito/workers/example'");
    SSH::assertNotExecutedContains('/home/example/.logs/workers');
});

test('cannot create worker as invalid user', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('workers.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'example',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
    ])
        ->assertSessionHasErrors();

    $this->assertDatabaseMissing('workers', [
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'user' => 'example',
    ]);
});

test('cannot create worker on another sites user', function () {
    SSH::fake();

    $this->actingAs($this->user);

    Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'example',
    ]);

    $this->post(route('workers.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'example',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
    ])
        ->assertSessionHasErrors();

    $this->assertDatabaseMissing('workers', [
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'user' => 'example',
    ]);
});

test('start worker', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::STOPPED,
    ]);

    $this->post(route('workers.start', [
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'status' => WorkerStatus::RUNNING,
    ]);
});

test('start worker reloads config before starting', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::STOPPED,
    ]);

    $this->post(route('workers.start', [
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('supervisorctl reread');
    SSH::assertExecutedContains("supervisorctl update {$worker->id}");
    SSH::assertExecutedContains("supervisorctl start {$worker->id}:*");
});

test('start and restart clear error optimistically', function () {
    Queue::fake();

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::FAILED,
        'error' => '10:10_00: ERROR (no such file)',
    ]);

    app(ManageWorker::class)->restart($worker);

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::RESTARTING);
    expect($worker->error)->toBeNull();

    $worker->status = WorkerStatus::FAILED;
    $worker->error = 'boom';
    $worker->save();

    app(ManageWorker::class)->start($worker);

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::STARTING);
    expect($worker->error)->toBeNull();

    Queue::assertPushed(ManageJob::class, 2);
});

test('start worker clears previous error', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::FAILED,
        'error' => '10:10_00: ERROR (no such file)',
    ]);

    $this->post(route('workers.start', [
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSessionDoesntHaveErrors();

    $worker->refresh();

    expect($worker->status)->toBe(WorkerStatus::RUNNING);
    expect($worker->error)->toBeNull();
});

test('stop worker', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    $this->post(route('workers.stop', [
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'status' => WorkerStatus::STOPPED,
    ]);
});

test('restart worker', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    $this->post(route('workers.restart', [
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'status' => WorkerStatus::RUNNING,
    ]);
});

test('show logs', function () {
    SSH::fake('logs');

    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    $this->get(route('workers.logs', [
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSuccessful();
});

test('worker logs use the persisted path instead of the current default', function (string $user, string $directory) {
    SSH::fake('legacy logs');
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'user' => $user,
        'stdout_logfile' => $directory.'/worker.log',
        'status' => WorkerStatus::RUNNING,
    ]);

    $this->actingAs($this->user)->get(route('workers.logs', [
        'server' => $this->server,
        'worker' => $worker,
    ]))->assertSuccessful();

    expect($worker->getLogFile())->toBe($worker->stdout_logfile);
    expect($worker->getLogDirectory())->toBe($directory);
    SSH::assertExecutedContains('tail -100 -- '.escapeshellarg($worker->stdout_logfile));
    SSH::assertNotExecutedContains('supervisorctl');
})->with([
    ['root', '/root/.logs/workers'],
    ['vito', '/home/vito/.logs/workers'],
    ['isolated', '/home/isolated/.logs/workers'],
    ['vito', "/home/vito/worker's logs"],
]);

test('worker config refresh preserves a legacy log path without privileged log preparation', function (string $path) {
    SSH::fake();
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'stdout_logfile' => $path,
    ]);
    /** @var ProcessManager $handler */
    $handler = $this->server->processManager()->handler();

    $handler->writeConfig($worker);

    expect(SSH::getUploadedContent())->toContain('stdout_logfile='.$worker->stdout_logfile);
    SSH::assertNotExecutedContains('sudo install');
    SSH::assertNotExecutedContains('sudo touch');
    SSH::assertNotExecutedContains('supervisorctl');
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'stdout_logfile' => $worker->stdout_logfile]);
})->with(['/home/vito/.logs/workers/legacy.log', "/home/vito/worker's logs/worker.log", '/root/.logs/workers/legacy.log']);

test('worker config refresh moves a legacy log out of an isolated user home once and carries recent lines over', function () {
    SSH::fake();
    $legacy = '/home/isolated/.logs/workers/legacy.log';
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'user' => 'isolated',
        'stdout_logfile' => $legacy,
    ]);
    /** @var ProcessManager $handler */
    $handler = $this->server->processManager()->handler();

    $handler->writeConfig($worker);
    $handler->writeConfig(Worker::query()->findOrFail($worker->id));

    $path = '/var/log/vito/workers/isolated/'.$worker->id.'.log';
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'stdout_logfile' => $path]);
    expect($worker->stdout_logfile)->toBe($path)
        ->and($worker->isDirty('stdout_logfile'))->toBeFalse()
        ->and(SSH::getUploadedContent())->toContain('stdout_logfile='.$path)->not->toContain($legacy)
        ->and(collect(SSH::getExecutedCommands())->filter(fn (string $command): bool => str_contains($command, $legacy)))->toHaveCount(1);
    SSH::assertExecutedContains("sudo -u 'isolated' tail -n 500 -- '".$legacy."' 2>/dev/null | sudo tee '".$path."' > /dev/null || true");
    SSH::assertExecutedContains("install -d -o root -g 'isolated' -m 750 '/var/log/vito/workers/isolated'");
});

test('worker log migration records existing locations without remote changes', function (string $user, bool $component) {
    SSH::fake();
    Queue::fake();
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'component_id' => $component ? Component::factory()->create(['server_id' => $this->server->id])->id : null,
        'user' => $user,
    ]);
    $recorded = Worker::factory()->create([
        'server_id' => $this->server->id,
        'stdout_logfile' => '/var/log/custom-worker.log',
    ]);
    $migration = require glob(database_path('migrations/*_backfill_worker_log_paths.php'))[0];

    $migration->up();
    $migration->up();

    $directory = $component ? '/var/log/vito/workers/'.$user : ($user === 'root' ? '/root' : '/home/'.$user).'/.logs/workers';
    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'stdout_logfile' => $directory.'/'.$worker->id.'.log',
        'updated_at' => $worker->getRawOriginal('updated_at'),
    ]);
    $this->assertDatabaseHas('workers', ['id' => $recorded->id, 'stdout_logfile' => '/var/log/custom-worker.log']);
    expect(SSH::getExecutedCommands())->toBeEmpty();
    Queue::assertNothingPushed();
})->with([
    ['root', false],
    ['vito', false],
    ['isolated', false],
    ['componentuser', true],
]);

test('worker logs use the default path before the worker is installed', function () {
    SSH::fake('pending logs');
    $worker = Worker::factory()->create(['server_id' => $this->server->id]);

    $this->actingAs($this->user)->get(route('workers.logs', [
        'server' => $this->server,
        'worker' => $worker,
    ]))->assertSuccessful();

    SSH::assertExecutedContains('tail -100 -- '.escapeshellarg('/var/log/vito/workers/vito/'.$worker->id.'.log'));
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'stdout_logfile' => null]);
});

test('worker deletion leaves legacy logs in place when an unprivileged user owns their home', function (string $path, bool $removed) {
    SSH::fake();
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'stdout_logfile' => $path,
    ]);

    $this->actingAs($this->user)->delete(route('workers.destroy', [
        'server' => $this->server,
        'worker' => $worker,
    ]))->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
    SSH::assertExecutedContains('supervisorctl stop '.$worker->id.':*');
    if ($removed) {
        SSH::assertExecutedContains('sudo sh -c '.escapeshellarg('rm -f -- '.escapeshellarg($path).' '.escapeshellarg($path).'.*'));
    } else {
        SSH::assertNotExecutedContains($path);
    }
})->with([
    ['/var/log/vito/workers/isolated/legacy.log', true],
    ['/root/.logs/workers/legacy.log', true],
    ['/home/vito/.logs/workers/legacy.log', true],
    ['/home/isolated/.logs/workers/legacy.log', false],
    ['/home/vitoextra/.logs/workers/legacy.log', false],
]);

test('worker edit records the newly installed log path when the user changes', function () {
    SSH::fake();
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'vito',
        'stdout_logfile' => '/home/vito/.logs/workers/legacy.log',
    ]);

    $this->actingAs($this->user)->put(route('workers.update', [
        'server' => $this->server,
        'worker' => $worker,
    ]), [
        'name' => $worker->name,
        'command' => $worker->command,
        'user' => 'root',
        'auto_start' => true,
        'auto_restart' => true,
        'numprocs' => 1,
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    $path = '/var/log/vito/workers/root/'.$worker->id.'.log';
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'stdout_logfile' => $path, 'user' => 'root']);
    expect(SSH::getUploadedContent())->toContain('stdout_logfile='.$path);
    SSH::assertExecutedContains('/home/vito/.logs/workers/legacy.log');
    SSH::assertExecutedContains("install -d -o root -g 'root' -m 750 '/var/log/vito/workers/root'");
});

test('create worker with valid site id', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->post(route('workers.store', [
        'server' => $this->server,
    ]), [
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
        'site_id' => $site->id,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('workers', [
        'server_id' => $this->server->id,
        'site_id' => $site->id,
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
        'status' => WorkerStatus::RUNNING,
    ]);
});

test('cannot create worker with invalid site id', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('workers.store', [
        'server' => $this->server,
    ]), [
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
        'site_id' => 99999, // Non-existent site ID
    ])
        ->assertSessionHasErrors(['site_id']);

    $this->assertDatabaseMissing('workers', [
        'server_id' => $this->server->id,
        'site_id' => 99999,
    ]);
});

test('cannot create worker with site id from different server', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var Server $otherServer */
    $otherServer = Server::factory()->create(['user_id' => 1]);

    /** @var Site $otherSite */
    $otherSite = Site::factory()->create([
        'server_id' => $otherServer->id,
    ]);

    $this->post(route('workers.store', [
        'server' => $this->server,
    ]), [
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
        'site_id' => $otherSite->id,
    ])
        ->assertSessionHasErrors(['site_id']);

    $this->assertDatabaseMissing('workers', [
        'server_id' => $this->server->id,
        'site_id' => $otherSite->id,
    ]);
});

test('edit worker with valid site id', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->put(route('workers.update', [
        'server' => $this->server,
        'worker' => $worker,
    ]), [
        'name' => $worker->name,
        'command' => 'updated command',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 2,
        'site_id' => $site->id,
    ])
        ->assertSessionDoesntHaveErrors();

    $worker->refresh();

    expect($worker->site_id)->toEqual($site->id);
    expect($worker->command)->toEqual('updated command');
    expect($worker->numprocs)->toEqual(2);
});

test('cannot edit worker with invalid site id', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->put(route('workers.update', [
        'server' => $this->server,
        'worker' => $worker,
    ]), [
        'name' => $worker->name,
        'command' => 'updated command',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 2,
        'site_id' => 99999, // Non-existent site ID
    ])
        ->assertSessionHasErrors(['site_id']);

    $worker->refresh();

    $this->assertNotEquals(99999, $worker->site_id);
});

test('cannot edit worker with site id from different server', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Server $otherServer */
    $otherServer = Server::factory()->create(['user_id' => 1]);

    /** @var Site $otherSite */
    $otherSite = Site::factory()->create([
        'server_id' => $otherServer->id,
    ]);

    $this->put(route('workers.update', [
        'server' => $this->server,
        'worker' => $worker,
    ]), [
        'name' => $worker->name,
        'command' => 'updated command',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 2,
        'site_id' => $otherSite->id,
    ])
        ->assertSessionHasErrors(['site_id']);

    $worker->refresh();

    $this->assertNotEquals($otherSite->id, $worker->site_id);
});

test('create worker with environment', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('workers.store', [
        'server' => $this->server,
    ]), [
        'name' => 'Test Worker',
        'command' => 'npm run start',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
        'environment' => [
            ['key' => 'PATH', 'value' => '/home/vito/.local/share/mise/shims:/usr/local/bin:/usr/bin:/bin', 'is_secret' => false],
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ],
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('workers', [
        'server_id' => $this->server->id,
        'name' => 'Test Worker',
        'command' => 'npm run start',
        'status' => WorkerStatus::RUNNING,
    ]);

    $worker = Worker::where('name', 'Test Worker')->first();
    expect($worker)->not->toBeNull();
    expect($worker->environment)->toEqual([
        ['key' => 'PATH', 'value' => '/home/vito/.local/share/mise/shims:/usr/local/bin:/usr/bin:/bin', 'is_secret' => false],
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
    ]);
});

test('cannot create worker with legacy environment map', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('workers.store', [
        'server' => $this->server,
    ]), [
        'name' => 'Test Worker',
        'command' => 'npm run start',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
        'environment' => [
            'NODE_ENV' => 'production',
        ],
    ])
        ->assertSessionHasErrors();
});

test('worker environment is stored as array', function () {
    $worker = Worker::factory()->withEnvironment([
        'PATH' => '/custom/path',
        'NODE_ENV' => 'production',
    ])->create([
        'server_id' => $this->server->id,
    ]);

    expect($worker->environment)->toBeArray();
    expect($worker->environmentMap()['PATH'])->toEqual('/custom/path');
    expect($worker->environmentMap()['NODE_ENV'])->toEqual('production');
});

test('cannot edit site bootstrap worker', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);

    $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->put(route('workers.update', [
        'server' => $this->server,
        'worker' => $worker,
    ]), [
        'name' => 'renamed',
        'command' => 'renamed command',
        'user' => 'vito',
        'auto_start' => 1,
        'auto_restart' => 1,
        'numprocs' => 1,
    ])
        ->assertSessionHasErrors(['name']);

    $this->assertDatabaseMissing('workers', [
        'id' => $worker->id,
        'command' => 'renamed command',
    ]);
});

test('cannot delete site bootstrap worker', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);

    $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->delete(route('workers.destroy', [
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertForbidden();

    $this->assertDatabaseHas('workers', ['id' => $worker->id]);
});

test('can still start stop restart bootstrap worker', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::STOPPED,
    ]);

    $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->post(route('workers.start', [
        'server' => $this->server,
        'worker' => $worker,
    ]))->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'status' => WorkerStatus::RUNNING,
    ]);
});

test('restart all with site id restarts only site workers', function () {
    SSH::fake();

    $siteWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);
    $serverWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::RUNNING,
    ]);
    $creatingWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::CREATING,
    ]);

    /** @var ProcessManager $handler */
    $handler = $this->server->processManager()->handler();
    $handler->restartAll($this->site->id);

    SSH::assertExecutedContains("supervisorctl update {$siteWorker->id};");
    SSH::assertExecutedContains("restart {$siteWorker->id}:*");
    SSH::assertNotExecutedContains("restart {$serverWorker->id}:*");
    SSH::assertNotExecutedContains("supervisorctl update {$creatingWorker->id};");
    SSH::assertNotExecutedContains('supervisorctl restart all');
});

test('restart all without site id updates config and restarts all', function () {
    SSH::fake();

    /** @var ProcessManager $handler */
    $handler = $this->server->processManager()->handler();
    $handler->restartAll();

    SSH::assertExecutedContains('supervisorctl update');
    SSH::assertExecutedContains('supervisorctl restart all');
});

test('restart all with site id without workers executes nothing', function () {
    SSH::fake();

    Worker::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var ProcessManager $handler */
    $handler = $this->server->processManager()->handler();
    $handler->restartAll($this->site->id);

    SSH::assertNotExecutedContains('supervisorctl');
});

test('resync updates worker statuses', function () {
    $this->actingAs($this->user);

    $siteWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::FAILED,
        'error' => 'old error',
    ]);
    $serverWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake(
        "{$siteWorker->id}:{$siteWorker->id}_00   RUNNING   pid 100, uptime 0:00:05\n".
        "{$serverWorker->id}:{$serverWorker->id}_00   STOPPED   Jun 06 10:00 AM"
    );

    $this->post(route('workers.resync', ['server' => $this->server]))
        ->assertSessionDoesntHaveErrors()
        ->assertSessionHas('success');

    SSH::assertExecutedContains('supervisorctl status');

    $siteWorker->refresh();
    $serverWorker->refresh();
    expect($siteWorker->status)->toBe(WorkerStatus::RUNNING);
    expect($siteWorker->error)->toBeNull();
    expect($serverWorker->status)->toBe(WorkerStatus::STOPPED);
});

test('resync marks fatal worker failed with error', function () {
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake("{$worker->id}:{$worker->id}_00   FATAL   Exited too quickly (process log may have details)");

    $this->post(route('workers.resync', ['server' => $this->server]))
        ->assertSessionDoesntHaveErrors();

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::FAILED);
    $this->assertStringContainsString('FATAL Exited too quickly', (string) $worker->error);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'sync-worker-statuses-failed',
    ]);
});

test('resync does not relog unchanged failed worker', function () {
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::FAILED,
    ]);
    $worker->error = "{$worker->id}:{$worker->id}_00: FATAL Exited too quickly";
    $worker->save();

    SSH::fake("{$worker->id}:{$worker->id}_00   FATAL   Exited too quickly");

    $this->post(route('workers.resync', ['server' => $this->server]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('server_logs', [
        'type' => 'sync-worker-statuses-failed',
    ]);

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::FAILED);
});

test('resync marks missing worker failed', function () {
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake('unix:///var/run/supervisor.sock no such file');

    $this->post(route('workers.resync', ['server' => $this->server]))
        ->assertSessionDoesntHaveErrors();

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::FAILED);
    expect($worker->error)->toBe('Process not found in supervisor');
});

test('resync site scope does not touch server workers', function () {
    $this->actingAs($this->user);

    $siteWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);
    $serverWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake("{$siteWorker->id}:{$siteWorker->id}_00   STOPPED   Jun 06 10:00 AM");

    $this->post(route('workers.resync', ['server' => $this->server, 'site' => $this->site]))
        ->assertSessionDoesntHaveErrors();

    $siteWorker->refresh();
    $serverWorker->refresh();
    expect($siteWorker->status)->toBe(WorkerStatus::STOPPED);
    expect($serverWorker->status)->toBe(WorkerStatus::RUNNING);
});

test('resync skips creating workers', function () {
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::CREATING,
    ]);

    SSH::fake("{$worker->id}:{$worker->id}_00   RUNNING   pid 100, uptime 0:00:05");

    $this->post(route('workers.resync', ['server' => $this->server]))
        ->assertSessionDoesntHaveErrors();

    SSH::assertNotExecutedContains('supervisorctl status');

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::CREATING);
});

test('restart all endpoint sets restarting and dispatches job', function () {
    Queue::fake();

    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::STOPPED,
        'error' => 'old error',
    ]);
    $creatingWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::CREATING,
    ]);

    $this->post(route('workers.restart-all', ['server' => $this->server]))
        ->assertSessionDoesntHaveErrors();

    $worker->refresh();
    $creatingWorker->refresh();
    expect($worker->status)->toBe(WorkerStatus::RESTARTING);
    expect($worker->error)->toBeNull();
    expect($creatingWorker->status)->toBe(WorkerStatus::CREATING);

    Queue::assertPushed(RestartAllJob::class);
});

test('restart all endpoint full flow settles statuses', function () {
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::STOPPED,
    ]);

    SSH::fake("{$worker->id}:{$worker->id}_00   RUNNING   pid 100, uptime 0:00:05");

    $this->post(route('workers.restart-all', ['server' => $this->server]))
        ->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('supervisorctl restart all');
    SSH::assertExecutedContains('supervisorctl status');

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::RUNNING);
});

test('restart all endpoint site scope uses restart many', function () {
    $this->actingAs($this->user);

    $siteWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake("{$siteWorker->id}:{$siteWorker->id}_00   RUNNING   pid 100, uptime 0:00:05");

    $this->post(route('workers.restart-all', ['server' => $this->server, 'site' => $this->site]))
        ->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains("restart {$siteWorker->id}:*");
    SSH::assertNotExecutedContains('supervisorctl restart all');

    $siteWorker->refresh();
    expect($siteWorker->status)->toBe(WorkerStatus::RUNNING);
});

test('restart all job failed marks restarting workers failed', function () {
    $restartingWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RESTARTING,
    ]);
    $stoppedWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::STOPPED,
    ]);

    (new RestartAllJob($this->server))->failed(new SSHCommandError('boom'));

    $restartingWorker->refresh();
    $stoppedWorker->refresh();
    expect($restartingWorker->status)->toBe(WorkerStatus::FAILED);
    expect($restartingWorker->error)->toBe('boom');
    expect($stoppedWorker->status)->toBe(WorkerStatus::STOPPED);
});

test('user role cannot resync or restart all', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    $this->actingAs($this->user);

    $this->post(route('workers.resync', ['server' => $this->server]))
        ->assertForbidden();

    $this->post(route('workers.restart-all', ['server' => $this->server]))
        ->assertForbidden();
});

test('worker resource marks site bootstrap', function () {
    $bootstrap = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);
    $other = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);

    $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $bootstrap->id);
    $bootstrap->refresh();
    $other->refresh();

    expect($bootstrap->isSiteBootstrap())->toBeTrue();
    expect($other->isSiteBootstrap())->toBeFalse();
});

test('worker routes reject a site from another server', function (string $method, string $route) {
    $ssh = SSH::fake();
    Queue::fake();

    $otherServer = Server::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->server->project_id,
    ]);
    $service = $this->server->webserver()->replicate();
    $service->server_id = $otherServer->id;
    $service->save();
    $site = Site::factory()->create(['server_id' => $otherServer->id]);

    $this->actingAs($this->user)->json($method, route($route, [
        'server' => $this->server,
        'site' => $site,
    ]), [
        'name' => 'Forbidden worker',
        'command' => 'php artisan queue:work',
        'user' => 'vito',
        'auto_start' => true,
        'auto_restart' => true,
        'numprocs' => 1,
    ])->assertForbidden();

    $this->assertDatabaseCount('workers', 0);
    expect($ssh->getExecutedCommands())->toBeEmpty();
    Queue::assertNothingPushed();
})->with([
    ['GET', 'workers.site'],
    ['POST', 'workers.store'],
    ['POST', 'workers.resync'],
    ['POST', 'workers.restart-all'],
]);

test('worker routes reject a worker from another site', function (string $method, string $route) {
    $ssh = SSH::fake();
    Queue::fake();

    $site = Site::factory()->create(['server_id' => $this->server->id]);
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);
    $attributes = $worker->refresh()->getAttributes();

    $this->actingAs($this->user)->json($method, route($route, [
        'server' => $this->server,
        'site' => $site,
        'worker' => $worker,
    ]), [
        'name' => 'Changed worker',
        'command' => 'pwd',
        'user' => 'vito',
        'auto_start' => true,
        'auto_restart' => true,
        'numprocs' => 1,
    ])->assertForbidden();

    $this->assertDatabaseHas('workers', ['id' => $worker->id]);
    expect($worker->refresh()->getAttributes())->toBe($attributes);
    expect($ssh->getExecutedCommands())->toBeEmpty();
    Queue::assertNothingPushed();
})->with([
    ['PUT', 'workers.update'],
    ['DELETE', 'workers.destroy'],
]);

test('worker policies validate site ownership while preserving server contexts', function (string $ability) {
    $resource = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);
    $subject = in_array($ability, ['viewAny', 'create', 'manage']) ? Worker::class : $resource;

    expect($this->user->can($ability, [$subject, $this->server]))->toBeTrue();
    expect($this->user->can($ability, [$subject, $this->server, $this->site]))->toBeTrue();

    $otherServer = Server::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->server->project_id,
    ]);
    $this->site->update(['server_id' => $otherServer->id]);

    expect($this->user->can($ability, [$subject, $this->server, $this->site]))->toBeFalse();
})->with(['viewAny', 'create', 'manage', 'view', 'update', 'delete']);
