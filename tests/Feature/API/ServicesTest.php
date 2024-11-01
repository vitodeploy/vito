<?php

namespace Tests\Feature\API;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_services_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('GET', route('api.projects.servers.services', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'mysql',
            ])
            ->assertJsonFragment([
                'name' => 'nginx',
            ])
            ->assertJsonFragment([
                'name' => 'php',
            ])
            ->assertJsonFragment([
                'name' => 'supervisor',
            ])
            ->assertJsonFragment([
                'name' => 'redis',
            ])
            ->assertJsonFragment([
                'name' => 'ufw',
            ]);
    }

    public function test_show_service(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);
        $service = $this->server->services()->firstOrFail();

        $this->json('GET', route('api.projects.servers.services.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'service' => $service,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => $service->name,
            ]);
    }

    /**
     * @dataProvider data
     */
    public function test_manage_service(string $action): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $service = $this->server->services()->firstOrFail();
        $service->status = ServiceStatus::STOPPED;
        $service->save();

        SSH::fake('Active: active');

        $this->json('POST', route('api.projects.servers.services.'.$action, [
            'project' => $this->server->project,
            'server' => $this->server,
            'service' => $service,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }

    public function test_uninstall_service(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $service = $this->server->services()->where('type', 'process_manager')->firstOrFail();

        SSH::fake();

        $this->json('DELETE', route('api.projects.servers.services.uninstall', [
            'project' => $this->server->project,
            'server' => $this->server,
            'service' => $service,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }

    public function test_cannot_uninstall_service_because_it_is_being_used(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $service = $this->server->services()->where('type', 'webserver')->firstOrFail();

        SSH::fake();

        $this->json('DELETE', route('api.projects.servers.services.uninstall', [
            'project' => $this->server->project,
            'server' => $this->server,
            'service' => $service,
        ]))
            ->assertJsonValidationErrorFor('service');
    }

    public static function data(): array
    {
        return [
            ['start'],
            ['stop'],
            ['restart'],
            ['enable'],
            ['disable'],
        ];
    }
}
