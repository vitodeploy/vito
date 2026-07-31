<?php

use App\Enums\DeploymentStatus;
use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\Deployment;
use App\Models\Site;
use App\Models\Worker;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proxiedSite = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-foo',
        'path' => '/home/isolated-foo/app.test',
        'type' => NodeSite::id(),
        'port' => 3000,
        'type_data' => [
            'node_version' => '22',
            'package_manager' => 'npm',
            'start_command' => 'npm start',
        ],
    ]);
});

test('after deploy creates worker when none exists', function () {
    SSH::fake();

    $type = $this->proxiedSite->type();
    $type->afterDeploy(Deployment::factory()->create([
        'site_id' => $this->proxiedSite->id,
        'status' => DeploymentStatus::DEPLOYING,
    ]));

    $worker = $this->proxiedSite->workers()->where('name', 'app')->first();
    expect($worker)->not->toBeNull();
    expect($worker->command)->toBe('npm start');

    $this->proxiedSite->refresh();
    expect($this->proxiedSite->type_data['bootstrap_worker_id'])->toBe($worker->id);
});

test('after deploy copies site worker environment to worker', function () {
    SSH::fake();

    $this->proxiedSite->worker_environment = [
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
    ];
    $this->proxiedSite->save();

    $type = $this->proxiedSite->type();
    $type->afterDeploy(Deployment::factory()->create([
        'site_id' => $this->proxiedSite->id,
        'status' => DeploymentStatus::DEPLOYING,
    ]));

    $worker = $this->proxiedSite->workers()->where('name', 'app')->first();
    expect($worker)->not->toBeNull();
    expect($worker->environment)->toEqual([
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
    ]);
    expect($worker->effectiveEnvironment()['NODE_ENV'])->toBe('production');
    expect($worker->effectiveEnvironment()['API_KEY'])->toBe('secret-value');
});

test('after deploy is idempotent when worker already recorded', function () {
    SSH::fake();

    $existing = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->proxiedSite->id,
        'user' => 'isolated-foo',
        'name' => 'app',
        'command' => 'npm start',
        'status' => WorkerStatus::RUNNING,
    ]);
    $this->proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $existing->id);

    $type = $this->proxiedSite->type();
    $type->afterDeploy(Deployment::factory()->create([
        'site_id' => $this->proxiedSite->id,
        'status' => DeploymentStatus::DEPLOYING,
    ]));

    expect($this->proxiedSite->workers()->where('name', 'app')->count())->toBe(1);
});

test('after deploy backfills by known default command', function () {
    SSH::fake();

    $existing = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->proxiedSite->id,
        'user' => 'isolated-foo',
        'name' => 'app',
        'command' => 'npm start',
        'status' => WorkerStatus::RUNNING,
    ]);

    $type = $this->proxiedSite->type();
    $type->afterDeploy(Deployment::factory()->create([
        'site_id' => $this->proxiedSite->id,
        'status' => DeploymentStatus::DEPLOYING,
    ]));

    $this->proxiedSite->refresh();
    expect($this->proxiedSite->type_data['bootstrap_worker_id'])->toBe($existing->id);
    expect($this->proxiedSite->workers()->where('name', 'app')->count())->toBe(1);
});

test('after deploy refuses to adopt user worker with custom command', function () {
    SSH::fake();

    Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->proxiedSite->id,
        'user' => 'isolated-foo',
        'name' => 'app',
        'command' => 'node custom-server.js',
        'status' => WorkerStatus::RUNNING,
    ]);

    $type = $this->proxiedSite->type();

    $type->afterDeploy(Deployment::factory()->create([
        'site_id' => $this->proxiedSite->id,
        'status' => DeploymentStatus::DEPLOYING,
    ]));
})->throws(ValidationException::class);
