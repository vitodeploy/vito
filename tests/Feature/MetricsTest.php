<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Web\Pages\Servers\Metrics\Index;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

        $this->get(Index::getUrl(['server' => $this->server]))
            ->assertSuccessful()
            ->assertSee('CPU Load')
            ->assertSee('Memory Usage')
            ->assertSee('Disk Usage');
    }

    public function test_cannot_visit_metrics(): void
    {
        $this->actingAs($this->user);

        $this->get(Index::getUrl(['server' => $this->server]))
            ->assertForbidden();
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

        Livewire::test(Index::class, [
            'server' => $this->server,
        ])
            ->callAction('data-retention', [
                'data_retention' => 365,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'type' => 'monitoring',
            'type_data->data_retention' => 365,
        ]);
    }
}
