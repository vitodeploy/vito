<?php

namespace Tests\Feature;

use App\Models\ServerLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_logs()
    {
        $this->actingAs($this->user);

        /** @var ServerLog $log */
        $log = ServerLog::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('servers.logs', $this->server))
            ->assertSuccessful()
            ->assertSeeText($log->type);
    }

    public function test_see_logs_remote()
    {
        $this->actingAs($this->user);

        /** @var ServerLog $log */
        $log = ServerLog::factory()->create([
            'server_id' => $this->server->id,
            'is_remote' => true,
            'type' => 'remote',
            'name' => 'see-remote-log',
        ]);

        $this->get(route('servers.logs.remote', $this->server))
            ->assertSuccessful()
            ->assertSeeText('see-remote-log');
    }

    public function test_create_remote_log()
    {
        $this->actingAs($this->user);

        $this->post(route('servers.logs.remote.store', [
            'server' => $this->server->id,
        ]), [
            'path' => 'test-path',
        ])->assertOk();

        $this->assertDatabaseHas('server_logs', [
            'is_remote' => true,
            'name' => 'test-path',
        ]);
    }
}
