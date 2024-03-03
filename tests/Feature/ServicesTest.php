<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Jobs\Installation\InstallPHPMyAdmin as InstallationInstallPHPMyAdmin;
use App\Jobs\Installation\UninstallPHPMyAdmin;
use App\Jobs\Service\Manage;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_services_list(): void
    {
        $this->actingAs($this->user);

        $this->get(route('servers.services', $this->server))
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

        Bus::fake();

        $this->get(route('servers.services.restart', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionHasNoErrors();

        Bus::assertDispatched(Manage::class);
    }

    /**
     * @dataProvider data
     */
    public function test_stop_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->first();

        Bus::fake();

        $this->get(route('servers.services.stop', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionHasNoErrors();

        Bus::assertDispatched(Manage::class);
    }

    /**
     * @dataProvider data
     */
    public function test_start_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->first();

        $service->status = ServiceStatus::STOPPED;
        $service->save();

        Bus::fake();

        $this->get(route('servers.services.start', [
            'server' => $this->server,
            'service' => $service,
        ]))->assertSessionHasNoErrors();

        Bus::assertDispatched(Manage::class);
    }

    public function test_install_phpmyadmin(): void
    {
        $this->markTestSkipped('PHPMyAdmin is depricated');

        Bus::fake();

        Bus::assertDispatched(InstallationInstallPHPMyAdmin::class);
    }

    public function test_uninstall_phpmyadmin(): void
    {
        $this->markTestSkipped('PHPMyAdmin is depricated');

        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'phpmyadmin',
            'type_data' => [
                'allowed_ip' => '0.0.0.0',
                'port' => '5433',
                'php' => '8.1',
            ],
            'name' => 'phpmyadmin',
            'version' => '5.1.2',
            'status' => ServiceStatus::READY,
            'is_default' => 1,

        ]);

        Bus::fake();

        Bus::assertDispatched(UninstallPHPMyAdmin::class);
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
        ];
    }
}
