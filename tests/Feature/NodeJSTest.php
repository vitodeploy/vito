<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NodeJSTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_default_nodejs_cli(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        /** @var Service $service */
        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'nodejs',
            'type_data' => [],
            'name' => 'nodejs',
            'version' => '16',
            'status' => ServiceStatus::READY,
            'is_default' => false,
        ]);

        $this->post(route('node.default-cli', [
            'server' => $this->server->id,
            'service' => $service->id,
        ]), [
            'version' => '16',
        ])
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertTrue($service->is_default);
    }
}
