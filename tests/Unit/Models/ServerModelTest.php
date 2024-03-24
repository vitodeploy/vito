<?php

namespace Tests\Unit\Models;

use App\Enums\ServerStatus;
use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_have_default_service()
    {
        $php = $this->server->defaultService('php');
        $php->update(['is_default' => false]);
        $this->assertNotNull($this->server->defaultService('php'));
        $php->refresh();
        $this->assertTrue($php->is_default);
    }

    public function test_check_connection_is_ready(): void
    {
        SSH::fake();

        $this->server->update(['status' => ServerStatus::DISCONNECTED]);

        $this->server->checkConnection();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::READY,
        ]);
    }

    public function test_connection_failed(): void
    {
        SSH::fake()->connectionWillFail();

        $this->server->update(['status' => ServerStatus::READY]);

        $this->server->checkConnection();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }
}
