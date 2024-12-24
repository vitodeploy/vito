<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Server;
use App\Web\Pages\Servers\Services\Index;
use App\Web\Pages\Servers\Services\Widgets\ServicesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_services_list(): void
    {
        $this->actingAs($this->user);

        $this->get(Index::getUrl(['server' => $this->server]))
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
        $service->status = ServiceStatus::STOPPED;
        $service->save();

        SSH::fake('Active: active');

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('restart', $service->id)
            ->assertSuccessful();

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

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('restart', $service->id)
            ->assertSuccessful();

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

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('stop', $service->id)
            ->assertSuccessful();

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

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('stop', $service->id)
            ->assertSuccessful();

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
        $service->status = ServiceStatus::STOPPED;
        $service->save();

        SSH::fake('Active: active');

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('start', $service->id)
            ->assertSuccessful();

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

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('start', $service->id)
            ->assertSuccessful();

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
        $service->status = ServiceStatus::DISABLED;
        $service->save();

        SSH::fake('Active: active');

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('enable', $service->id)
            ->assertSuccessful();

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

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('enable', $service->id)
            ->assertSuccessful();

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

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('disable', $service->id)
            ->assertSuccessful();

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

        Livewire::test(ServicesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('disable', $service->id)
            ->assertSuccessful();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    /**
     * @dataProvider installData
     */
    public function test_install_service(string $name, string $type, string $version): void
    {
        Http::fake([
            'https://api.github.com/repos/vito/vito-agent/releases/latest' => Http::response([
                'tag_name' => '0.1.0',
            ]),
        ]);
        SSH::fake('Active: active');

        $this->actingAs($this->user);

        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $keys = $server->sshKey();
        if (! File::exists($keys['public_key_path']) || ! File::exists($keys['private_key_path'])) {
            $server->provider()->generateKeyPair();
        }

        Livewire::test(Index::class, [
            'server' => $server,
        ])
            ->callAction('install', [
                'name' => $name,
                'version' => $version,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('services', [
            'server_id' => $server->id,
            'name' => $name,
            'type' => $type,
            'status' => ServiceStatus::READY,
        ]);
    }

    public static function data(): array
    {
        return [
            ['nginx'],
        ];
    }

    public static function installData(): array
    {
        return [
            [
                'nginx',
                'webserver',
                'latest',
            ],
            [
                'php',
                'php',
                '7.4',
            ],
            [
                'nodejs',
                'nodejs',
                '16',
            ],
            [
                'supervisor',
                'process_manager',
                'latest',
            ],
            [
                'redis',
                'memory_database',
                'latest',
            ],
            [
                'mysql',
                'database',
                '8.0',
            ],
            [
                'mariadb',
                'database',
                '10.4',
            ],
            [
                'postgresql',
                'database',
                '16',
            ],
            [
                'vito-agent',
                'monitoring',
                'latest',
            ],
        ];
    }
}
