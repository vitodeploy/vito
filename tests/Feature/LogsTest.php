<?php

namespace Tests\Feature;

use App\Models\ServerLog;
use App\Web\Pages\Servers\Logs\Index;
use App\Web\Pages\Servers\Logs\RemoteLogs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

        $this->get(Index::getUrl(['server' => $this->server]))
            ->assertSuccessful()
            ->assertSee($log->name);
    }

    public function test_see_logs_remote()
    {
        $this->actingAs($this->user);

        ServerLog::factory()->create([
            'server_id' => $this->server->id,
            'is_remote' => true,
            'type' => 'remote',
            'name' => 'see-remote-log',
        ]);

        $this->get(RemoteLogs::getUrl(['server' => $this->server]))
            ->assertSuccessful()
            ->assertSee('see-remote-log');
    }

    public function test_create_remote_log()
    {
        $this->actingAs($this->user);

        Livewire::test(RemoteLogs::class, ['server' => $this->server])
            ->callAction('create', [
                'path' => 'test-path',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('server_logs', [
            'is_remote' => true,
            'name' => 'test-path',
        ]);
    }
}
