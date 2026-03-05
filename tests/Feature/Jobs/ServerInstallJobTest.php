<?php

namespace Tests\Feature\Jobs;

use App\Enums\ServerStatus;
use App\Jobs\Server\InstallJob;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ServerInstallJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_job_failed_sets_status_to_installation_failed_and_logs(): void
    {
        Notification::fake();

        $this->server->update(['status' => ServerStatus::INSTALLING]);

        $job = new InstallJob($this->server);
        $job->failed(new Exception('Installation failed'));

        $this->server->refresh();

        $this->assertEquals(ServerStatus::INSTALLATION_FAILED, $this->server->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'server-installation-failed',
        ]);
    }
}
