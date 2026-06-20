<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkerEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_env_masks_secret_values(): void
    {
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
    }

    public function test_update_env_without_restart_rewrites_config_only(): void
    {
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
        $this->assertEquals([
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ], $worker->environment);
        $this->assertSame(WorkerStatus::RUNNING, $worker->status);

        $raw = (string) DB::table('workers')->where('id', $worker->id)->value('environment');
        $this->assertStringNotContainsString('production', $raw);

        $fake->assertExecutedContains("/etc/supervisor/conf.d/{$worker->id}.conf");
        $fake->assertNotExecutedContains('supervisorctl restart');
    }

    public function test_update_env_with_restart_restarts_worker(): void
    {
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
    }

    public function test_update_env_keeps_stored_secret_when_value_is_empty(): void
    {
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
        $this->assertEquals([
            ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ], $worker->environment);
    }

    public function test_update_env_replaces_secret_when_new_value_given(): void
    {
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

        $this->assertEquals([
            ['key' => 'API_KEY', 'value' => 'new-secret', 'is_secret' => true],
        ], $worker->refresh()->environment);
    }

    public function test_update_env_stored_secret_cannot_be_wiped_by_marking_it_non_secret(): void
    {
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

        $this->assertEquals([
            ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
        ], $worker->refresh()->environment);
    }

    public function test_update_env_validates_input(): void
    {
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
    }

    public function test_update_env_allowed_for_site_bootstrap_worker(): void
    {
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

        $this->assertEquals([
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ], $worker->refresh()->environment);
    }

    public function test_read_only_user_cannot_update_env(): void
    {
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
    }
}
