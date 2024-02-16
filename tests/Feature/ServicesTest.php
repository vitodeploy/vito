<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Http\Livewire\Services\InstallPHPMyAdmin;
use App\Http\Livewire\Services\ServicesList;
use App\Jobs\Installation\InstallPHPMyAdmin as InstallationInstallPHPMyAdmin;
use App\Jobs\Installation\UninstallPHPMyAdmin;
use App\Jobs\Service\Manage;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_services_list(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ServicesList::class, ['server' => $this->server])
            ->assertSee([
                'nginx',
                'php',
                'supervisor',
                'redis',
                'ufw',
                'php',
            ]);
    }

    /**
     * @dataProvider data
     */
    public function test_restart_service(string $name): void
    {
        $service = $this->server->services()->where('name', $name)->first();

        Bus::fake();

        Livewire::test(ServicesList::class, ['server' => $this->server])
            ->call('restart', $service->id)
            ->assertSuccessful();

        Bus::assertDispatched(Manage::class);
    }

    /**
     * @dataProvider data
     */
    public function test_stop_service(string $name): void
    {
        $service = $this->server->services()->where('name', $name)->first();

        Bus::fake();

        Livewire::test(ServicesList::class, ['server' => $this->server])
            ->call('stop', $service->id)
            ->assertSuccessful();

        Bus::assertDispatched(Manage::class);
    }

    /**
     * @dataProvider data
     */
    public function test_start_service(string $name): void
    {
        $service = $this->server->services()->where('name', $name)->first();

        $service->status = ServiceStatus::STOPPED;
        $service->save();

        Bus::fake();

        Livewire::test(ServicesList::class, ['server' => $this->server])
            ->call('start', $service->id)
            ->assertSuccessful();

        Bus::assertDispatched(Manage::class);
    }

    public function test_install_phpmyadmin(): void
    {
        Bus::fake();

        Livewire::test(InstallPHPMyAdmin::class, ['server' => $this->server])
            ->set('allowed_ip', '0.0.0.0')
            ->set('port', 5433)
            ->call('install')
            ->assertSuccessful();

        Bus::assertDispatched(InstallationInstallPHPMyAdmin::class);
    }

    public function test_uninstall_phpmyadmin(): void
    {
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

        Livewire::test(ServicesList::class, ['server' => $this->server])
            ->call('uninstall', $service->id)
            ->assertSuccessful();

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
