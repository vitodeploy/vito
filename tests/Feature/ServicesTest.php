<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_services_list(): void
    {
        $this->actingAs($this->user);

        $this->get(route('services', [
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('services/index'));
    }

    public function test_services_index_returns_the_inertia_table(): void
    {
        $this->actingAs($this->user);

        $this->get(route('services', ['server' => $this->server]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('services/index')
                ->where('refreshing', false)
                ->has('services.columns')
                ->has('services.data.0.resource')
                ->has('services.data.0.networked')
            );
    }

    #[DataProvider('data')]
    public function test_restart_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();
        $service->status = ServiceStatus::STOPPED;
        $service->save();

        SSH::fake('Active: active');

        $this->post(route('services.restart', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::READY, $service->status);
    }

    #[DataProvider('data')]
    public function test_reload_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();
        $service->status = ServiceStatus::READY;
        $service->save();

        SSH::fake('Active: active');

        $this->post(route('services.reload', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::READY, $service->status);
    }

    #[DataProvider('data')]
    public function test_failed_to_reload_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->post(route('services.reload', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    #[DataProvider('data')]
    public function test_failed_to_restart_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->post(route('services.restart', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    #[DataProvider('data')]
    public function test_stop_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->post(route('services.stop', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::STOPPED, $service->status);
    }

    #[DataProvider('data')]
    public function test_failed_to_stop_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: active');

        $this->post(route('services.stop', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    #[DataProvider('data')]
    public function test_start_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();
        $service->status = ServiceStatus::STOPPED;
        $service->save();

        SSH::fake('Active: active');

        $this->post(route('services.start', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::READY, $service->status);
    }

    #[DataProvider('data')]
    public function test_failed_to_start_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->post(route('services.start', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    #[DataProvider('data')]
    public function test_enable_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();
        $service->status = ServiceStatus::DISABLED;
        $service->save();

        SSH::fake('Active: active');

        $this->post(route('services.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::READY, $service->status);
    }

    #[DataProvider('data')]
    public function test_failed_to_enable_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->post(route('services.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    #[DataProvider('data')]
    public function test_disable_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: inactive');

        $this->post(route('services.disable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::DISABLED, $service->status);
    }

    #[DataProvider('data')]
    public function test_failed_to_disable_service(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->server->services()->where('name', $name)->firstOrFail();

        SSH::fake('Active: active');

        $this->post(route('services.disable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
    }

    #[DataProvider('installData')]
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

        $this->post(route('services.store', [
            'server' => $server,
        ]), [
            'name' => $name,
            'version' => $version,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('services', [
            'server_id' => $server->id,
            'name' => $name,
            'type' => $type,
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_install_service_creates_installation_log(): void
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

        $this->post(route('services.store', [
            'server' => $server,
        ]), [
            'name' => 'redis',
            'version' => 'latest',
        ])
            ->assertSessionDoesntHaveErrors();

        $service = $server->services()->where('name', 'redis')->firstOrFail();

        // Verify that the installation log is linked to the service
        $this->assertNotNull($service->log_id);
        $this->assertNotNull($service->log);
        $this->assertStringStartsWith('install-redis', $service->log->type);

        $this->assertNull(
            $service->type_data['networking_effective'] ?? null,
            'Install never observes the live bind state, so it must not claim one.'
        );
        $this->assertNull($service->type_data['networking_checked_at'] ?? null);
        $this->assertArrayNotHasKey('networking', $service->type_data);
    }

    #[DataProvider('phpVersionOutputData')]
    public function test_parse_php_installed_version(string $sshOutput, string $expectedVersion): void
    {
        /** @var Service $service */
        $service = $this->server->services()->where('name', 'php')->firstOrFail();

        $this->assertEquals($expectedVersion, $service->handler()->parseVersionOutput($sshOutput));
    }

    public function test_version_falls_back_to_the_raw_output_when_it_cannot_be_parsed(): void
    {
        SSH::fake('no version here');

        /** @var Service $service */
        $service = $this->server->services()->where('name', 'php')->firstOrFail();

        $this->assertEquals('no version here', $service->handler()->version());
    }

    public function test_php_version_command_escapes_the_configured_version(): void
    {
        /** @var Service $service */
        $service = $this->server->services()->where('name', 'php')->firstOrFail();
        $service->version = "8.3'; rm -rf /tmp; echo '";

        $this->assertEquals(
            '/usr/bin/php\'8.3\'\\\'\'; rm -rf /tmp; echo \'\\\'\'\' -r \'echo PHP_VERSION;\' 2>/dev/null',
            $service->handler()->versionCommand()
        );
    }

    public function test_services_version_route_is_removed(): void
    {
        $this->assertFalse(Route::has('services.version'));
    }

    /**
     * @return array<array<string>>
     */
    public static function phpVersionOutputData(): array
    {
        return [
            'clean version' => ['8.4.10', '8.4.10'],
            'version with noise' => ["Deprecated: some deprecation notice in php\n8.5.2", '8.5.2'],
            'version with whitespace' => ["  8.5.1\n", '8.5.1'],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function data(): array
    {
        return [
            ['nginx'],
            ['php'],
            ['supervisor'],
            ['redis'],
            ['mysql'],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function installData(): array
    {
        return [
            [
                'nginx',
                'webserver',
                'latest',
            ],
            [
                'caddy',
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
                'goaccess',
                'log_analysis',
                'latest',
            ],
            [
                'redis',
                'memory_database',
                'latest',
            ],
            [
                'valkey',
                'memory_database',
                'latest',
            ],
            [
                'mysql',
                'database',
                '8.4',
            ],
            [
                'mariadb',
                'database',
                '10.11',
            ],
            [
                'postgresql',
                'database',
                '16',
            ],
        ];
    }
}
