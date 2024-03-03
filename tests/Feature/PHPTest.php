<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Jobs\Installation\InstallPHP;
use App\Jobs\Installation\UninstallPHP;
use App\Jobs\PHP\InstallPHPExtension;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PHPTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_new_php(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.php.install', [
            'server' => $this->server,
            'version' => '8.1',
        ]))->assertSessionHasNoErrors();

        Bus::assertDispatched(InstallPHP::class);
    }

    public function test_uninstall_php(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $php = new Service([
            'server_id' => $this->server->id,
            'type' => 'php',
            'type_data' => [
                'extensions' => [],
                'settings' => config('core.php_settings'),
            ],
            'name' => 'php',
            'version' => '8.1',
            'status' => ServiceStatus::READY,
            'is_default' => true,
        ]);
        $php->save();

        $this->delete(route('servers.php.uninstall', [
            'server' => $this->server,
            'version' => '8.1',
        ]))->assertSessionHasNoErrors();

        Bus::assertDispatched(UninstallPHP::class);
    }

    public function test_cannot_uninstall_php(): void
    {
        $this->actingAs($this->user);

        $this->delete(route('servers.php.uninstall', [
            'server' => $this->server,
            'version' => '8.2',
        ]))->assertSessionHasErrors();
    }

    public function test_change_default_php_cli(): void
    {
        $this->actingAs($this->user);

        $php = Service::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'php',
            'type_data' => [
                'extensions' => [],
            ],
            'name' => 'php',
            'version' => '8.1',
            'status' => ServiceStatus::READY,
        ]);

        $this->post(route('servers.php.default-cli', [
            'server' => $this->server,
            'version' => '8.1',
        ]))->assertSessionHasNoErrors();

        $php->refresh();

        $this->assertTrue($php->is_default);
    }

    public function test_install_extension(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.php.install-extension', [
            'server' => $this->server,
            'version' => '8.2',
            'extension' => 'gmp',
        ]))->assertSessionHasNoErrors();

        Bus::assertDispatched(InstallPHPExtension::class);
    }

    public function test_extension_already_installed(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $this->server->php('8.2')->update([
            'type_data' => [
                'extensions' => [
                    'gmp',
                ],
            ],
        ]);

        $this->post(route('servers.php.install-extension', [
            'server' => $this->server,
            'version' => '8.2',
            'extension' => 'gmp',
        ]))->assertSessionHasErrors();

        Bus::assertNotDispatched(InstallPHPExtension::class);
    }
}
