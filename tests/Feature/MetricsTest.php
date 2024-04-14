<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->get(route('servers.metrics', ['server' => $this->server]))
            ->assertSuccessful()
            ->assertSee('CPU Load')
            ->assertSee('Memory Usage')
            ->assertSee('Disk Usage');
    }

    public function test_cannot_visit_metrics(): void
    {
        $this->actingAs($this->user);

        $this->get(route('servers.metrics', ['server' => $this->server]))
            ->assertNotFound();
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

        $this->post(route('servers.metrics.settings', ['server' => $this->server]), [
            'data_retention' => 30,
        ])->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'type' => 'monitoring',
            'type_data->data_retention' => 30,
        ]);
    }
}
