<?php

namespace Tests\Feature\API;

use App\Enums\OperatingSystem;
use App\Facades\SSH;
use App\ServerProviders\Custom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_servers(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('GET', route('api.projects.servers', [
            'project' => $this->user->current_project_id,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => $this->server->name,
            ]);
    }

    public function test_get_server(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('GET', route('api.projects.servers.show', [
            'project' => $this->user->current_project_id,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => $this->server->name,
            ]);
    }

    public function test_create_server(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake('Active: active'); // fake output for service installations

        $this->json('POST', route('api.projects.servers.create', [
            'project' => $this->user->current_project_id,
        ]), [
            'provider' => Custom::id(),
            'name' => 'test',
            'ip' => '1.1.1.1',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22,
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'test',
            ]);
    }

    public function test_delete_server(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake();

        $this->json('DELETE', route('api.projects.servers.delete', [
            'project' => $this->server->project_id,
            'server' => $this->server->id,
        ]))
            ->assertNoContent();
    }

    public function test_reboot_server(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.reboot', [
            'project' => $this->server->project_id,
            'server' => $this->server->id,
        ]))
            ->assertNoContent();
    }

    public function test_upgrade_server(): void
    {
        SSH::fake('Available updates:0');

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.upgrade', [
            'project' => $this->server->project_id,
            'server' => $this->server->id,
        ]))
            ->assertNoContent();
    }
}
