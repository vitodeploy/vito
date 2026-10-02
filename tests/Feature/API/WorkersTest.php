<?php

use App\Enums\ServerStatus;
use App\Enums\UserRole;
use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Jobs\Worker\RestartAllJob;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('see server workers list', function () {
    Sanctum::actingAs($this->user, ['read']);

    $this->json('GET', route('api.projects.servers.workers', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertSuccessful();
});

test('see site workers list', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.workers', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))
        ->assertSuccessful();
});

test('see server worker', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('GET', route('api.projects.servers.workers.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSuccessful();
});

test('see site worker', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
        'site_id' => $site->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.workers.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
        'worker' => $worker,
    ]))
        ->assertSuccessful();
});

test('create server worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.workers.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'vito',
        'auto_start' => true,
        'auto_restart' => true,
        'numprocs' => 1,
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'status' => WorkerStatus::CREATING,
        ]);

    $this->assertDatabaseHas('workers', [
        'status' => WorkerStatus::RUNNING,
        'name' => 'Test Worker',
    ]);
});

test('create site worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('POST', route('api.projects.servers.workers.create', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]), [
        'name' => 'Test Worker',
        'command' => 'php artisan worker:work',
        'user' => 'vito',
        'auto_start' => true,
        'auto_restart' => true,
        'numprocs' => 1,
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'status' => WorkerStatus::CREATING,
        ]);

    $this->assertDatabaseHas('workers', [
        'site_id' => $site->id,
        'status' => WorkerStatus::RUNNING,
        'name' => 'Test Worker',
    ]);
});

test('update server worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
        'numprocs' => 1,
    ]);

    $this->json('PUT', route('api.projects.servers.workers.update', [
        'project' => $this->server->project,
        'server' => $this->server,
        'worker' => $worker,
    ]), [
        'name' => $worker->name,
        'command' => $worker->command,
        'user' => $worker->user,
        'auto_start' => $worker->auto_start,
        'auto_restart' => $worker->auto_restart,
        'numprocs' => 2,
    ])
        ->assertSuccessful();

    $this->assertDatabaseHas('workers', [
        'numprocs' => 2,
    ]);
});

test('update site worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
        'site_id' => $site->id,
        'numprocs' => 1,
    ]);

    $this->json('PUT', route('api.projects.servers.workers.update', [
        'project' => $this->server->project,
        'server' => $this->server,
        'worker' => $worker,
        'site' => $site,
    ]), [
        'name' => $worker->name,
        'command' => $worker->command,
        'user' => $worker->user,
        'auto_start' => $worker->auto_start,
        'auto_restart' => $worker->auto_restart,
        'numprocs' => 2,
    ])
        ->assertSuccessful();

    $this->assertDatabaseHas('workers', [
        'site_id' => $site->id,
        'numprocs' => 2,
    ]);
});

test('start worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('POST', route('api.projects.servers.workers.start', [
        'project' => $this->server->project,
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'status' => WorkerStatus::STARTING,
        ]);
});

test('restart worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('POST', route('api.projects.servers.workers.restart', [
        'project' => $this->server->project,
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'status' => WorkerStatus::RESTARTING,
        ]);
});

test('delete server worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('DELETE', route('api.projects.servers.workers.delete', [
        'project' => $this->server->project,
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSuccessful()
        ->assertNoContent();
});

test('see worker logs', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read']);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('GET', route('api.projects.servers.workers.logs', [
        'project' => $this->server->project,
        'server' => $this->server,
        'worker' => $worker,
    ]))
        ->assertSuccessful()
        ->assertExactJson(['logs' => 'fake output']);
});

test('delete site worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
        'site_id' => $site->id,
    ]);

    $this->json('DELETE', route('api.projects.servers.workers.delete', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
        'worker' => $worker,
    ]))
        ->assertSuccessful()
        ->assertNoContent();
});

test('cannot update site bootstrap worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
        'site_id' => $site->id,
        'numprocs' => 1,
    ]);

    $site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->json('PUT', route('api.projects.servers.workers.update', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
        'worker' => $worker,
    ]), [
        'name' => 'renamed',
        'command' => 'renamed command',
        'user' => $worker->user,
        'auto_start' => $worker->auto_start,
        'auto_restart' => $worker->auto_restart,
        'numprocs' => 2,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);

    $this->assertDatabaseMissing('workers', [
        'id' => $worker->id,
        'command' => 'renamed command',
    ]);
});

test('cannot delete site bootstrap worker', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server,
        'site_id' => $site->id,
    ]);

    $site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->json('DELETE', route('api.projects.servers.workers.delete', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
        'worker' => $worker,
    ]))
        ->assertForbidden();

    $this->assertDatabaseHas('workers', ['id' => $worker->id]);
});

test('resync workers', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::FAILED,
    ]);

    SSH::fake("{$worker->id}:{$worker->id}_00   RUNNING   pid 100, uptime 0:00:05");

    $this->json('POST', route('api.projects.servers.workers.resync', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertExactJson(['synced' => 1]);

    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'status' => WorkerStatus::RUNNING,
    ]);
});

test('restart all workers', function () {
    Queue::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    $this->json('POST', route('api.projects.servers.workers.restart-all', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertStatus(202);

    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'status' => WorkerStatus::RESTARTING,
    ]);

    Queue::assertPushed(RestartAllJob::class);
});

test('cannot resync workers without write ability', function () {
    Sanctum::actingAs($this->user, ['read']);

    $this->json('POST', route('api.projects.servers.workers.resync', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertForbidden();
});

test('cannot resync workers for site on another server', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Server $otherServer */
    $otherServer = Server::factory()->create(['user_id' => 1]);

    /** @var Site $otherSite */
    $otherSite = Site::factory()->create([
        'server_id' => $otherServer->id,
    ]);

    $this->json('POST', route('api.projects.servers.workers.resync', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $otherSite,
    ]))
        ->assertNotFound();
});

test('user role with a write bearer token cannot create workers', function (bool $siteWorker) {
    $ssh = SSH::fake();
    Queue::fake();

    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);
    $token = $this->user->createToken('worker-token', ['read', 'write', 'project:'.$this->server->project_id]);

    $this->withToken($token->plainTextToken)->postJson(route('api.projects.servers.workers.create', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $siteWorker ? $this->site : null,
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
})->with([false, true]);

test('admin with a write bearer token can create and delete workers', function (bool $siteWorker) {
    SSH::fake();

    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::ADMIN,
    ]);
    $token = $this->user->createToken('worker-token', ['read', 'write', 'project:'.$this->server->project_id]);
    $parameters = [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $siteWorker ? $this->site : null,
    ];

    $response = $this->withToken($token->plainTextToken)->postJson(route('api.projects.servers.workers.create', $parameters), [
        'name' => 'Admin worker',
        'command' => 'php artisan queue:work',
        'user' => 'vito',
        'auto_start' => true,
        'auto_restart' => true,
        'numprocs' => 1,
    ])->assertSuccessful();

    $workerId = $response->json('id');
    $this->assertDatabaseHas('workers', ['id' => $workerId, 'status' => WorkerStatus::RUNNING]);

    $this->deleteJson(route('api.projects.servers.workers.delete', $parameters + ['worker' => $workerId]))
        ->assertNoContent();

    $this->assertDatabaseMissing('workers', ['id' => $workerId]);
})->with([false, true]);

test('worker api routes enforce worker server readiness', function (string $method, string $route) {
    $ssh = SSH::fake();
    Queue::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);
    $attributes = $worker->refresh()->getAttributes();
    $this->server->update(['status' => ServerStatus::INSTALLING]);

    $this->json($method, route($route, [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
        'worker' => $worker,
    ]), [
        'name' => 'Changed worker',
        'command' => 'pwd',
        'user' => 'vito',
        'auto_start' => true,
        'auto_restart' => true,
        'numprocs' => 1,
    ])->assertForbidden();

    $this->assertDatabaseCount('workers', 1);
    $this->assertDatabaseHas('workers', ['id' => $worker->id]);
    expect($worker->refresh()->getAttributes())->toBe($attributes);
    expect($ssh->getExecutedCommands())->toBeEmpty();
    Queue::assertNothingPushed();
})->with([
    ['GET', 'api.projects.servers.workers'],
    ['GET', 'api.projects.servers.sites.workers'],
    ['GET', 'api.projects.servers.workers.show'],
    ['GET', 'api.projects.servers.sites.workers.show'],
    ['POST', 'api.projects.servers.workers.resync'],
    ['POST', 'api.projects.servers.workers.restart-all'],
    ['POST', 'api.projects.servers.workers.create'],
    ['PUT', 'api.projects.servers.workers.update'],
    ['POST', 'api.projects.servers.workers.start'],
    ['POST', 'api.projects.servers.workers.restart'],
    ['GET', 'api.projects.servers.workers.logs'],
    ['DELETE', 'api.projects.servers.workers.delete'],
]);

test('worker api routes keep mismatched resource responses not found', function (string $mismatch) {
    $ssh = SSH::fake();
    Queue::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherServer = Server::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->server->project_id,
    ]);
    $site = Site::factory()->create(['server_id' => $this->server->id]);
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $site->id,
    ]);

    if ($mismatch === 'project') {
        $otherServer->update(['project_id' => Project::factory()->create()->id]);
    }

    $this->getJson(route('api.projects.servers.sites.workers.show', [
        'project' => $this->server->project,
        'server' => in_array($mismatch, ['project', 'server']) ? $otherServer : $this->server,
        'site' => $mismatch === 'site' ? $this->site : $site,
        'worker' => $worker,
    ]))->assertNotFound();

    expect($ssh->getExecutedCommands())->toBeEmpty();
    Queue::assertNothingPushed();
})->with(['project', 'server', 'site']);
