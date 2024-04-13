<?php

namespace Tests\Unit\Actions\Service;

use App\Actions\Service\Uninstall;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UninstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_uninstall_vito_agent(): void
    {
        SSH::fake();

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        app(Uninstall::class)->uninstall($this->server->monitoring());

        $this->assertDatabaseMissing('services', [
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    /**
     * Cannot uninstall nginx because some sites using it
     */
    public function test_cannot_uninstall_nginx(): void
    {
        SSH::fake();

        $this->expectException(ValidationException::class);

        app(Uninstall::class)->uninstall($this->server->webserver());
    }

    /**
     * Cannot uninstall mysql because some databases exist
     */
    public function test_cannot_uninstall_mysql(): void
    {
        SSH::fake();

        Database::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->expectException(ValidationException::class);

        app(Uninstall::class)->uninstall($this->server->database());
    }

    /**
     * Cannot uninstall supervisor because some queues exist
     */
    public function test_cannot_uninstall_supervisor(): void
    {
        SSH::fake();

        Queue::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->expectException(ValidationException::class);

        app(Uninstall::class)->uninstall($this->server->processManager());
    }
}
