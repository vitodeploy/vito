<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceConfigFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_config_file(): void
    {
        $this->actingAs($this->user);

        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'mysql',
            'type' => 'database',
        ]);

        SSH::fake('config file content');

        $response = $this->get(route('services.config', [
            'server' => $this->server,
            'service' => $service->id,
            'config_name' => 'my.cnf',
        ]));

        $response->assertSuccessful()
            ->assertJson([
                'content' => 'config file content',
            ]);
    }

    public function test_get_config_file_not_found(): void
    {
        $this->actingAs($this->user);

        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'mysql',
            'type' => 'database',
        ]);

        $response = $this->get(route('services.config', [
            'server' => $this->server,
            'service' => $service->id,
            'config_name' => 'nonexistent.conf',
        ]));

        $response->assertSessionHasErrors(['config_name']);
    }

    public function test_update_config_file(): void
    {
        $this->actingAs($this->user);

        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'mysql',
            'type' => 'database',
        ]);

        SSH::fake('Active: active');

        $response = $this->patch(route('services.config.update', [
            'server' => $this->server,
            'service' => $service->id,
        ]), [
            'config_name' => 'my.cnf',
            'content' => 'new config content',
        ]);

        $response->assertSessionDoesntHaveErrors()
            ->assertRedirect();
    }

    public function test_update_config_file_validates_input(): void
    {
        $this->actingAs($this->user);

        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'mysql',
            'type' => 'database',
        ]);

        $response = $this->patch(route('services.config.update', [
            'server' => $this->server,
            'service' => $service->id,
        ]), [
            'config_name' => 'my.cnf',
        ]);

        $response->assertSessionHasErrors(['content']);
    }

    public function test_service_without_config_paths_returns_error(): void
    {
        $this->actingAs($this->user);

        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
        ]);

        $response = $this->get(route('services.config', [
            'server' => $this->server,
            'service' => $service->id,
            'config_name' => 'test.conf',
        ]));

        $response->assertSessionHasErrors(['config_paths']);
    }
}
