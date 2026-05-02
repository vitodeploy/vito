<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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

    #[DataProvider('phpVersionOutputData')]
    public function test_fetch_php_installed_version(string $sshOutput, string $expectedVersion): void
    {
        SSH::fake($sshOutput);

        $this->actingAs($this->user);

        /** @var Service $service */
        $service = $this->server->services()->where('name', 'php')->firstOrFail();

        $this->get(route('services.version', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertEquals($expectedVersion, $service->refresh()->installed_version);
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
        ];
    }
}
