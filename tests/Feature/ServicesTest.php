<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_services_list(): void
    {
        $this->actingAs($this->user);

        $this->get(route('servers.services', $this->server))
            ->assertSuccessful()
            ->assertSee('mysql')
            ->assertSee('nginx')
            ->assertSee('php')
            ->assertSee('supervisor')
            ->assertSee('redis')
            ->assertSee('ufw');
    }

    /**
     * @dataProvider data
     */
    public function test_restart_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: active');

        $this->get(route('servers.services.restart', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::READY, $service->status);
    }

    /**
     * @dataProvider data
     */
    public function test_failed_to_restart_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->get(route('servers.services.restart', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    /**
     * @dataProvider data
     */
    public function test_stop_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->get(route('servers.services.stop', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::STOPPED, $service->status);
    }

    /**
     * @dataProvider data
     */
    public function test_failed_to_stop_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: active');

        $this->get(route('servers.services.stop', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    /**
     * @dataProvider data
     */
    public function test_start_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: active');

        $this->get(route('servers.services.start', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::READY, $service->status);
    }

    /**
     * @dataProvider data
     */
    public function test_failed_to_start_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->get(route('servers.services.start', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    /**
     * @dataProvider data
     */
    public function test_enable_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: active');

        $this->get(route('servers.services.enable', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::READY, $service->status);
    }

    /**
     * @dataProvider data
     */
    public function test_failed_to_enable_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->get(route('servers.services.enable', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    /**
     * @dataProvider data
     */
    public function test_disable_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->get(route('servers.services.disable', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::DISABLED, $service->status);
    }

    /**
     * @dataProvider data
     */
    public function test_failed_to_disable_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: active');

        $this->get(route('servers.services.disable', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    public static function data(): array
    {
        return [
            ['nginx'],
            ['php'],
            ['supervisor'],
            ['redis'],
            ['ufw'],
            ['php'],
            ['mysql'],
        ];
    }
}
