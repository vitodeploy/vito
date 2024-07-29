<?php

namespace Tests\Feature;

use App\Enums\PHPIniType;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PHPTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_new_php(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.php.install', [
            'server' => $this->server,
            'version' => '8.1',
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'type' => 'php',
            'version' => '8.1',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_uninstall_php(): void
    {
        SSH::fake();

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
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('services', [
            'id' => $php->id,
        ]);
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
        SSH::fake();

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
        ]))->assertSessionDoesntHaveErrors();

        $php->refresh();

        $this->assertTrue($php->is_default);
    }

    public function test_install_extension(): void
    {
        SSH::fake('output... [PHP Modules] gmp');

        $this->actingAs($this->user);

        $this->post(route('servers.php.install-extension', [
            'server' => $this->server,
            'version' => '8.2',
            'extension' => 'gmp',
        ]))->assertSessionDoesntHaveErrors();

        $php = $this->server->php('8.2');

        $this->assertContains('gmp', $php->type_data['extensions']);
    }

    public function test_extension_already_installed(): void
    {
        SSH::fake();

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
    }

    /**
     * @dataProvider php_ini_data
     */
    public function test_get_php_ini(string $version, string $type): void
    {
        SSH::fake('[PHP ini]');

        $this->actingAs($this->user);

        $this->get(route('servers.php.get-ini', [
            'server' => $this->server,
            'version' => $version,
            'type' => $type,
        ]))->assertSessionHas('ini');
    }

    /**
     * @dataProvider php_ini_data
     */
    public function test_update_php_ini(string $version, string $type): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.php.update-ini', [
            'server' => $this->server,
            'version' => $version,
            'type' => $type,
            'ini' => 'new ini',
        ]))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('toast.type', 'success')
            ->assertSessionHas('toast.message', __('PHP ini (:type) updated!', ['type' => $type]));
    }

    public static function php_ini_data(): array
    {
        return [
            ['8.2', PHPIniType::FPM],
            ['8.2', PHPIniType::CLI],
        ];
    }
}
