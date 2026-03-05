<?php

namespace Tests\Feature\Jobs;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Service\ManageJob;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_job_handle_sets_ready_on_active_response(): void
    {
        SSH::fake('Active: active');

        $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
        $service->status = ServiceStatus::RESTARTING;
        $service->save();

        $job = new ManageJob($service, 'restart', ServiceStatus::READY);
        $job->handle();

        $service->refresh();

        $this->assertEquals(ServiceStatus::READY, $service->status);
    }

    public function test_manage_job_handle_sets_failed_on_inactive_response(): void
    {
        SSH::fake('Active: inactive');

        $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
        $service->status = ServiceStatus::RESTARTING;
        $service->save();

        $job = new ManageJob($service, 'restart', ServiceStatus::READY);
        $job->handle();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    public function test_manage_job_stop_expects_inactive_state(): void
    {
        SSH::fake('Active: inactive');

        $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
        $service->status = ServiceStatus::STOPPING;
        $service->save();

        $job = new ManageJob($service, 'stop', ServiceStatus::STOPPED);
        $job->handle();

        $service->refresh();

        $this->assertEquals(ServiceStatus::STOPPED, $service->status);
    }

    public function test_manage_job_disable_expects_inactive_state(): void
    {
        SSH::fake('Active: inactive');

        $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
        $service->status = ServiceStatus::DISABLING;
        $service->save();

        $job = new ManageJob($service, 'disable', ServiceStatus::DISABLED);
        $job->handle();

        $service->refresh();

        $this->assertEquals(ServiceStatus::DISABLED, $service->status);
    }

    public function test_manage_job_failed_sets_status_to_failed_and_logs(): void
    {
        $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
        $service->status = ServiceStatus::RESTARTING;
        $service->save();

        $job = new ManageJob($service, 'restart', ServiceStatus::READY);
        $job->failed(new Exception('SSH connection failed'));

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'restart-service-failed',
        ]);
    }

    public function test_manage_job_stop_failed_logs_correct_type(): void
    {
        $service = $this->server->services()->where('name', 'nginx')->firstOrFail();

        $job = new ManageJob($service, 'stop', ServiceStatus::STOPPED);
        $job->failed(new Exception('SSH connection failed'));

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'stop-service-failed',
        ]);
    }
}
