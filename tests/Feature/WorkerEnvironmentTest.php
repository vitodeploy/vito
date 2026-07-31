<?php

use App\Enums\UserRole;
use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('get env masks secret values', function () {
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'environment' => [
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
            ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
        ],
    ]);

    $this->get(route('workers.env', ['server' => $this->server, 'worker' => $worker]))
        ->assertOk()
        ->assertJson([
            'variables' => [
                ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
                ['key' => 'API_KEY', 'value' => '', 'is_secret' => true],
            ],
        ]);
});

test('update env without restart rewrites config only', function () {
    $fake = SSH::fake();
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    $this->patch(route('workers.update-env', ['server' => $this->server, 'worker' => $worker]), [
        'variables' => [
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ],
    ])->assertRedirect()
        ->assertSessionHas('warning');

    $worker->refresh();
    expect($worker->environment)->toEqual([
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
    ]);
    expect($worker->status)->toBe(WorkerStatus::RUNNING);

    $raw = (string) DB::table('workers')->where('id', $worker->id)->value('environment');
    $this->assertStringNotContainsString('production', $raw);

    $fake->assertExecutedContains("/etc/supervisor/conf.d/{$worker->id}.conf");
    $fake->assertNotExecutedContains('supervisorctl restart');
});

test('update env with restart restarts worker', function () {
    $fake = SSH::fake();
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    $this->patch(route('workers.update-env', ['server' => $this->server, 'worker' => $worker]), [
        'variables' => [
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ],
        'restart' => true,
    ])->assertRedirect()
        ->assertSessionHas('info');

    $fake->assertExecutedContains("/etc/supervisor/conf.d/{$worker->id}.conf");
    $fake->assertExecutedContains("supervisorctl restart {$worker->id}:*");
});

test('update env keeps stored secret when value is empty', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'environment' => [
            ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
        ],
    ]);

    $this->patch(route('workers.update-env', ['server' => $this->server, 'worker' => $worker]), [
        'variables' => [
            ['key' => 'API_KEY', 'value' => '', 'is_secret' => true],
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ],
    ])->assertSessionDoesntHaveErrors();

    $worker->refresh();
    expect($worker->environment)->toEqual([
        ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
    ]);
});

test('update env replaces secret when new value given', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'environment' => [
            ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
        ],
    ]);

    $this->patch(route('workers.update-env', ['server' => $this->server, 'worker' => $worker]), [
        'variables' => [
            ['key' => 'API_KEY', 'value' => 'new-secret', 'is_secret' => true],
        ],
    ])->assertSessionDoesntHaveErrors();

    expect($worker->refresh()->environment)->toEqual([
        ['key' => 'API_KEY', 'value' => 'new-secret', 'is_secret' => true],
    ]);
});

test('update env stored secret cannot be wiped by marking it non secret', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'environment' => [
            ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
        ],
    ]);

    $this->patch(route('workers.update-env', ['server' => $this->server, 'worker' => $worker]), [
        'variables' => [
            ['key' => 'API_KEY', 'value' => '', 'is_secret' => false],
        ],
    ])->assertSessionDoesntHaveErrors();

    expect($worker->refresh()->environment)->toEqual([
        ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
    ]);
});

test('update env validates input', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $url = route('workers.update-env', ['server' => $this->server, 'worker' => $worker]);

    $this->patch($url, [
        'variables' => [['key' => 'foo bar', 'value' => 'x', 'is_secret' => false]],
    ])->assertSessionHasErrors('variables.0.key');

    $this->patch($url, [
        'variables' => [['key' => '1INVALID', 'value' => 'x', 'is_secret' => false]],
    ])->assertSessionHasErrors('variables.0.key');

    $this->patch($url, [
        'variables' => [['key' => 'FOO', 'value' => "a\nb", 'is_secret' => false]],
    ])->assertSessionHasErrors('variables.0.value');

    $this->patch($url, [
        'variables' => [['key' => 'FOO', 'value' => 'a"b', 'is_secret' => false]],
    ])->assertSessionHasErrors('variables.0.value');

    $this->patch($url, [
        'variables' => [
            ['key' => 'FOO', 'value' => 'a', 'is_secret' => false],
            ['key' => 'FOO', 'value' => 'b', 'is_secret' => false],
        ],
    ])->assertSessionHasErrors(['variables.0.key', 'variables.1.key']);

    $this->patch($url, [
        'variables' => collect(range(1, 101))
            ->map(fn (int $i): array => ['key' => "VAR_{$i}", 'value' => 'x', 'is_secret' => false])
            ->all(),
    ])->assertSessionHasErrors('variables');
});

test('update env allowed for site bootstrap worker', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'name' => 'app',
        'status' => WorkerStatus::RUNNING,
    ]);
    $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->patch(route('workers.update-env', ['server' => $this->server, 'worker' => $worker]), [
        'variables' => [
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ],
    ])->assertSessionDoesntHaveErrors();

    expect($worker->refresh()->environment)->toEqual([
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
    ]);
});

test('read only user cannot update env', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    $this->actingAs($this->user);

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->patch(route('workers.update-env', ['server' => $this->server, 'worker' => $worker]), [
        'variables' => [],
    ])->assertForbidden();
});
