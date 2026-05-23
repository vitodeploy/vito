<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Models\Metric;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_metrics(): void
    {
        $this->actingAs($this->user);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->get(route('monitoring', $this->server))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('monitoring/index'));
    }

    public function test_update_data_retention(): void
    {
        $this->actingAs($this->user);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->patch(route('monitoring.update', $this->server), [
            'data_retention' => 365,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'type' => 'monitoring',
            'type_data->data_retention' => 365,
        ]);
    }

    public function test_monitoring_json_returns_current_and_history(): void
    {
        $this->actingAs($this->user);

        Metric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_cores' => 4,
            'cpu_physical_cores' => 2,
            'cpu_usage_percent' => 12.34,
            'memory_total' => 2000,
            'memory_used' => 1010,
            'memory_free' => 990,
            'swap_used_percent' => 0,
            'disk_total' => 1000,
            'disk_used' => 250,
            'disk_free' => 750,
            'uptime_seconds' => 3600,
            'reboot_required' => false,
        ]);

        $response = $this->getJson(route('monitoring.json', ['server' => $this->server, 'period' => '10m']));

        $response->assertSuccessful()
            ->assertJsonStructure([
                'current' => [
                    'date',
                    'cpu_cores',
                    'cpu_physical_cores',
                    'cpu_usage_percent',
                    'memory_used_percent',
                    'swap_used_percent',
                    'disk_used_percent',
                    'uptime_seconds',
                    'reboot_required',
                ],
                'history' => [],
            ])
            ->assertJsonPath('current.cpu_cores', 4)
            ->assertJsonPath('current.cpu_usage_percent', 12.34)
            ->assertJsonPath('current.disk_used_percent', 25)
            ->assertJsonPath('current.memory_used_percent', 50.5)
            ->assertJsonPath('current.uptime_seconds', 3600);

        $history = $response->json('history');
        $this->assertIsArray($history);
        $this->assertNotEmpty($history);
        foreach (['cpu_cores', 'cpu_physical_cores', 'uptime_seconds', 'reboot_required', 'date_interval'] as $excluded) {
            $this->assertArrayNotHasKey($excluded, $history[0]);
        }
        $this->assertArrayHasKey('load', $history[0]);
        $this->assertArrayHasKey('cpu_usage_percent', $history[0]);
        $this->assertArrayHasKey('disk_used_percent', $history[0]);
        $this->assertEquals(25.0, $history[0]['disk_used_percent']);
        $this->assertArrayHasKey('memory_used_percent', $history[0]);
        $this->assertEquals(50.5, $history[0]['memory_used_percent']);

        foreach (['load', 'memory_total', 'cpu_usage_percent', 'disk_used_percent'] as $key) {
            if ($history[0][$key] !== null) {
                $this->assertIsNumeric($history[0][$key], "history.0.{$key} should be numeric (not a string)");
                $this->assertIsNotString($history[0][$key], "history.0.{$key} must not be serialised as a JSON string");
            }
        }
    }

    public function test_monitoring_json_returns_null_current_when_no_metrics(): void
    {
        $this->actingAs($this->user);

        $this->getJson(route('monitoring.json', ['server' => $this->server, 'period' => '10m']))
            ->assertSuccessful()
            ->assertJsonPath('current', null)
            ->assertJsonPath('history', []);
    }

    public function test_monitoring_json_defaults_period_when_missing(): void
    {
        $this->actingAs($this->user);

        $this->getJson(route('monitoring.json', ['server' => $this->server]))
            ->assertSuccessful()
            ->assertJsonPath('current', null)
            ->assertJsonPath('history', []);
    }
}
