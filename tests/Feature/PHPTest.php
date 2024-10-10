<?php

namespace Tests\Feature;

use App\Enums\PHPIniType;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Service;
use App\Web\Pages\Servers\PHP\Index;
use App\Web\Pages\Servers\PHP\Widgets\PHPList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PHPTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_new_php(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(Index::class, ['server' => $this->server])
            ->callAction('install', [
                'version' => '8.1',
            ])
            ->assertSuccessful();

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

        Livewire::test(PHPList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('uninstall', $php->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('services', [
            'id' => $php->id,
        ]);
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
            'is_default' => false,
        ]);

        Livewire::test(PHPList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('default-php-cli', $php->id)
            ->assertSuccessful();

        $php->refresh();

        $this->assertTrue($php->is_default);
    }

    public function test_install_extension(): void
    {
        SSH::fake('output... [PHP Modules] gmp');

        $this->actingAs($this->user);

        Livewire::test(PHPList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('install-extension', $this->server->php()->id, [
                'extension' => 'gmp',
            ])
            ->assertSuccessful();

        $php = $this->server->php('8.2');

        $this->assertContains('gmp', $php->type_data['extensions']);
    }

    /**
     * @dataProvider php_ini_data
     */
    public function test_get_php_ini(string $version, string $type): void
    {
        SSH::fake('[PHP ini]');

        $this->actingAs($this->user);

        Livewire::test(PHPList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('php-ini-'.$type, $this->server->php()->id, [
                'ini' => 'new-ini',
            ])
            ->assertSuccessful();
    }

    public static function php_ini_data(): array
    {
        return [
            ['8.2', PHPIniType::FPM],
            ['8.2', PHPIniType::CLI],
        ];
    }
}
