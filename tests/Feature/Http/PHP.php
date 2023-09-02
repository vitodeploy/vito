<?php

namespace Tests\Feature\Http;

use App\Enums\ServiceStatus;
use App\Http\Livewire\Php\DefaultCli;
use App\Http\Livewire\Php\InstalledVersions;
use App\Jobs\Installation\InstallPHP;
use App\Jobs\Installation\UninstallPHP;
use App\Jobs\PHP\SetDefaultCli;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class PHP extends TestCase
{
    use RefreshDatabase;

    public function test_install_new_php(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        Livewire::test(InstalledVersions::class, ['server' => $this->server])
            ->call('install', '8.1')
            ->assertSuccessful();

        Bus::assertDispatched(InstallPHP::class);
    }

    public function test_uninstall_new_php(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        Livewire::test(InstalledVersions::class, ['server' => $this->server])
            ->set('uninstallId', $this->server->php('8.2')?->id)
            ->call('uninstall')
            ->assertSuccessful();

        Bus::assertDispatched(UninstallPHP::class);
    }

    public function test_change_default_php_cli(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        Service::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'php',
            'type_data' => [
                'extensions' => [],
            ],
            'name' => 'php',
            'version' => '8.1',
            'status' => ServiceStatus::READY,
        ]);

        Livewire::test(DefaultCli::class, ['server' => $this->server])
            ->call('change', '8.1')
            ->assertSuccessful();

        Bus::assertDispatched(SetDefaultCli::class);
    }
}
