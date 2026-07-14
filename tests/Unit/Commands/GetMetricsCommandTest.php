<?php

namespace Tests\Unit\Commands;

use App\Enums\ServiceStatus;
use App\Events\ServiceStatusChanged;
use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GetMetricsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_metrics(): void
    {
        SSH::fake(<<<'EOF'
            load:1
            memory_total:1
            memory_used:1
            memory_free:1
            disk_total:1
            disk_used:1
            disk_free:1
            cpu_cores:4
            cpu_physical_cores:2
            swap_total:1024
            swap_used:256
            swap_free:768
            swap_used_percent:25.00
            uptime_seconds:12345.67
            reboot_required:1
            oom_kill_count:3
            cpu_usage_and_steal:1.23|0.45
        EOF);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'remote-monitor',
            'type' => 'monitoring',
            'type_data' => [
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->artisan('metrics:get')
            ->expectsOutput('Checked 1 metrics');

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'load' => 1,
            'memory_total' => 1,
            'memory_used' => 1,
            'memory_free' => 1,
            'disk_total' => 1,
            'disk_used' => 1,
            'disk_free' => 1,
            'cpu_cores' => 4,
            'cpu_physical_cores' => 2,
            'cpu_usage_percent' => 1.23,
            'cpu_steal_percent' => 0.45,
            'swap_total' => 1024,
            'swap_used' => 256,
            'swap_free' => 768,
            'swap_used_percent' => 25.00,
            'uptime_seconds' => 12345.67,
            'reboot_required' => true,
            'oom_kill_count' => 3,
            'cpu_per_core_usage_percent' => null,
        ]);
    }

    public function test_checks_service_statuses(): void
    {
        SSH::fake('active');
        Event::fake([ServiceStatusChanged::class]);

        $this->server->services()->delete();
        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'remote-monitor',
            'type' => 'monitoring',
            'type_data' => [
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
        $nginx = $this->server->services()->create([
            'type' => 'webserver',
            'name' => 'nginx',
            'version' => 'latest',
            'status' => ServiceStatus::STOPPED,
        ]);

        $this->artisan('metrics:get')->assertSuccessful();

        SSH::assertExecutedContains('is-active');
        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        Event::assertDispatched(fn (ServiceStatusChanged $event): bool => $event->service->id === $nginx->id
            && $event->previousStatus === ServiceStatus::STOPPED
            && $event->newStatus === ServiceStatus::READY);
    }

    public function test_get_metrics_with_missing_extended_fields(): void
    {
        SSH::fake(<<<'EOF'
            load:1
            memory_total:1
            memory_used:1
            memory_free:1
            disk_total:1
            disk_used:1
            disk_free:1
        EOF);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'remote-monitor',
            'type' => 'monitoring',
            'type_data' => [
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->artisan('metrics:get')
            ->expectsOutput('Checked 1 metrics');

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'load' => 1,
            'cpu_cores' => null,
            'cpu_usage_percent' => null,
            'cpu_steal_percent' => null,
            'swap_total' => null,
            'uptime_seconds' => null,
            'oom_kill_count' => null,
            'reboot_required' => false,
        ]);
    }
}
