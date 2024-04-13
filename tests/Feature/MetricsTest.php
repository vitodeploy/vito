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
            ->assertSee('CPU Load')
            ->assertSee('Memory Usage')
            ->assertSee('Disk Usage')
            ->assertSee('Resource Usage');
    }

    public function test_cannot_visit_metrics(): void
    {
        $this->actingAs($this->user);

        $this->get(route('servers.metrics', ['server' => $this->server]))
            ->assertRedirect(route('servers.services', ['server' => $this->server]));
    }
}
