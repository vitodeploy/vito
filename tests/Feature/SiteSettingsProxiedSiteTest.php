<?php

use App\Enums\DeploymentStatus;
use App\Enums\WorkerStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Models\Deployment;
use App\Models\Site;
use App\Models\Worker;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;

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

    $this->actingAs($this->user);
});

test('settings page exposes proxied site flags', function () {
    $this->get(route('site-settings', ['server' => $this->server, 'site' => $this->proxiedSite]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $a) => $a
            ->component('site-settings/index')
            ->where('site.is_proxied_site_type', true)
            ->where('site.start_command', 'npm start')
            ->where('site.port', 3000)
        );
});

test('update port updates site and regenerates vhost', function () {
    SSH::fake();
    Event::fake([SocketEvent::class]);

    $this->patch(route('site-settings.update-port', ['server' => $this->server, 'site' => $this->proxiedSite]), [
        'port' => 4000,
    ])->assertRedirect();

    $this->proxiedSite->refresh();
    expect($this->proxiedSite->port)->toBe(4000);

    Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event) => $event->data->type === 'site.updated');
});

test('update port validates', function () {
    SSH::fake();

    $this->patch(route('site-settings.update-port', ['server' => $this->server, 'site' => $this->proxiedSite]), [
        'port' => 99999,
    ])->assertSessionHasErrors('port');
});

test('update start command rejects newline injection', function () {
    SSH::fake();

    $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
        'start_command' => "npm start\nuser=root",
    ])->assertSessionHasErrors('start_command');

    $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
        'start_command' => "npm start\rcommand=/bin/sh",
    ])->assertSessionHasErrors('start_command');

    $this->proxiedSite->refresh();
    expect($this->proxiedSite->type_data['start_command'])->toBe('npm start');
});

test('update start command pre first deploy stores only', function () {
    SSH::fake();

    $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
        'start_command' => 'pnpm start',
    ])->assertRedirect()
        ->assertSessionHas('info');

    $this->proxiedSite->refresh();
    expect($this->proxiedSite->type_data['start_command'])->toBe('pnpm start');
    expect($this->proxiedSite->workers()->where('name', 'app')->first())->toBeNull();
});

test('update start command with existing worker updates conf no restart', function () {
    $fake = SSH::fake();

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->proxiedSite->id,
        'user' => 'isolated-foo',
        'name' => 'app',
        'command' => 'npm start',
        'status' => WorkerStatus::RUNNING,
    ]);
    $this->proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
        'start_command' => 'pnpm start',
    ])->assertRedirect()
        ->assertSessionHas('warning');

    $worker->refresh();
    expect($worker->command)->toBe('pnpm start');

    $fake->assertNotExecutedContains('supervisorctl restart');
});

test('update start command with restart rewrites config and restarts', function () {
    $fake = SSH::fake();

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->proxiedSite->id,
        'user' => 'isolated-foo',
        'name' => 'app',
        'command' => 'npm start',
        'status' => WorkerStatus::RUNNING,
    ]);
    $this->proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->patch(route('site-settings.update-start-command', ['server' => $this->server, 'site' => $this->proxiedSite]), [
        'start_command' => 'pnpm start',
        'restart' => true,
    ])->assertRedirect()
        ->assertSessionHas('info');

    $worker->refresh();
    expect($worker->command)->toBe('pnpm start');

    $fake->assertExecutedContains("/etc/supervisor/conf.d/{$worker->id}.conf");
    $fake->assertExecutedContains("supervisorctl restart {$worker->id}:*");
});

test('worker env pre deploy returns masked site variables', function () {
    $this->proxiedSite->worker_environment = [
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
    ];
    $this->proxiedSite->save();

    $this->get(route('site-settings.worker-env', ['server' => $this->server, 'site' => $this->proxiedSite]))
        ->assertOk()
        ->assertJson([
            'variables' => [
                ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
                ['key' => 'API_KEY', 'value' => '', 'is_secret' => true],
            ],
        ]);
});

test('update worker env pre deploy stores encrypted on site', function () {
    $fake = SSH::fake();

    $this->patch(route('site-settings.update-worker-env', ['server' => $this->server, 'site' => $this->proxiedSite]), [
        'variables' => [
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ],
    ])->assertRedirect()
        ->assertSessionHas('info');

    $this->proxiedSite->refresh();
    expect($this->proxiedSite->worker_environment)->toEqual([
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
    ]);

    $raw = (string) DB::table('sites')->where('id', $this->proxiedSite->id)->value('worker_environment');
    $this->assertStringNotContainsString('production', $raw);

    $fake->assertNotExecutedContains('supervisor');
});

test('update worker env with existing worker delegates to worker', function () {
    $fake = SSH::fake();

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->proxiedSite->id,
        'user' => 'isolated-foo',
        'name' => 'app',
        'command' => 'npm start',
        'status' => WorkerStatus::RUNNING,
    ]);
    $this->proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->patch(route('site-settings.update-worker-env', ['server' => $this->server, 'site' => $this->proxiedSite]), [
        'variables' => [
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ],
        'restart' => true,
    ])->assertRedirect()
        ->assertSessionHas('info');

    $worker->refresh();
    expect($worker->environment)->toEqual([
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
    ]);
    expect($this->proxiedSite->refresh()->worker_environment)->toBeNull();

    $fake->assertExecutedContains("/etc/supervisor/conf.d/{$worker->id}.conf");
    $fake->assertExecutedContains("supervisorctl restart {$worker->id}:*");
});

test('worker env endpoints 404 for non proxied site', function () {
    $this->get(route('site-settings.worker-env', ['server' => $this->server, 'site' => $this->site]))
        ->assertNotFound();

    $this->patch(route('site-settings.update-worker-env', ['server' => $this->server, 'site' => $this->site]), [
        'variables' => [],
    ])->assertNotFound();
});

test('needs first deploy warning present until finished deployment', function () {
    $beforeDeploy = $this->get(route('site-settings', ['server' => $this->server, 'site' => $this->proxiedSite]));
    $beforeDeploy->assertInertia(fn (AssertableInertia $a) => $a
        ->where('site.warnings', fn ($warnings) => collect($warnings)->contains(fn ($w) => $w['key'] === 'needs_first_deploy'))
    );

    Deployment::factory()->create([
        'site_id' => $this->proxiedSite->id,
        'status' => DeploymentStatus::FINISHED,
    ]);

    $afterDeploy = $this->get(route('site-settings', ['server' => $this->server, 'site' => $this->proxiedSite]));
    $afterDeploy->assertInertia(fn (AssertableInertia $a) => $a
        ->where('site.warnings', fn ($warnings) => ! collect($warnings)->contains(fn ($w) => $w['key'] === 'needs_first_deploy'))
    );
});
