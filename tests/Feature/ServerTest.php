<?php

namespace Tests\Feature;

use App\Enums\Database;
use App\Enums\OperatingSystem;
use App\Enums\ServerProvider;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Enums\Webserver;
use App\Facades\SSH;
use App\NotificationChannels\Email\NotificationMail;
use App\Web\Pages\Servers\Index;
use App\Web\Pages\Servers\Settings;
use App\Web\Pages\Servers\Widgets\ServerDetails;
use App\Web\Pages\Servers\Widgets\ServerSummary;
use App\Web\Pages\Servers\Widgets\UpdateServerInfo;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_regular_server(): void
    {
        $this->actingAs($this->user);

        SSH::fake('Active: active'); // fake output for service installations

        Livewire::test(Index::class)
            ->callAction('create', [
                'provider' => ServerProvider::CUSTOM,
                'name' => 'test',
                'ip' => '1.1.1.1',
                'port' => '22',
                'os' => OperatingSystem::UBUNTU22,
                'webserver' => Webserver::NGINX,
                'database' => Database::MYSQL80,
                'php' => '8.2',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('servers', [
            'name' => 'test',
            'ip' => '1.1.1.1',
            'status' => ServerStatus::READY,
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => 1,
            'type' => 'php',
            'version' => '8.2',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => 1,
            'type' => 'webserver',
            'name' => 'nginx',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => 1,
            'type' => 'database',
            'name' => 'mysql',
            'version' => '8.0',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => 1,
            'type' => 'firewall',
            'name' => 'ufw',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_delete_server(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        Livewire::test(Settings::class, [
            'server' => $this->server,
        ])->callAction('delete')
            ->assertSuccessful()
            ->assertRedirect(Index::getUrl());

        $this->assertDatabaseMissing('servers', [
            'id' => $this->server->id,
        ]);
    }

    public function test_cannot_delete_on_provider(): void
    {
        Mail::fake();
        Http::fake([
            '*' => Http::response([], 401),
        ]);

        $this->actingAs($this->user);

        $provider = \App\Models\ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => ServerProvider::HETZNER,
            'credentials' => [
                'token' => 'token',
            ],
        ]);

        $this->server->update([
            'provider' => ServerProvider::HETZNER,
            'provider_id' => $provider->id,
            'provider_data' => [
                'hetzner_id' => 1,
                'ssh_key_id' => 1,
            ],
        ]);

        Livewire::test(Settings::class, [
            'server' => $this->server,
        ])->callAction('delete')
            ->assertSuccessful()
            ->assertRedirect(Index::getUrl());

        $this->assertDatabaseMissing('servers', [
            'id' => $this->server->id,
        ]);

        Mail::assertSent(NotificationMail::class);
    }

    public function test_check_connection_is_ready(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->server->update(['status' => ServerStatus::DISCONNECTED]);

        Livewire::test(ServerSummary::class, [
            'server' => $this->server,
        ])
            ->callInfolistAction('status', 'check-status')
            ->assertSuccessful()
            ->assertNotified('Server is '.ServerStatus::READY);

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::READY,
        ]);
    }

    public function test_connection_failed(): void
    {
        SSH::fake()->connectionWillFail();

        $this->actingAs($this->user);

        $this->server->update(['status' => ServerStatus::READY]);

        Livewire::test(ServerSummary::class, [
            'server' => $this->server,
        ])
            ->callInfolistAction('status', 'check-status')
            ->assertSuccessful()
            ->assertNotified('Server is '.ServerStatus::DISCONNECTED);

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }

    public function test_reboot_server(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(Settings::class, [
            'server' => $this->server,
        ])
            ->callAction('reboot')
            ->assertSuccessful();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }

    public function test_edit_server(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(UpdateServerInfo::class, [
            'server' => $this->server,
        ])
            ->fill([
                'name' => 'new-name',
                'ip' => $this->server->ip,
                'port' => $this->server->port,
            ])
            ->call('submit')
            ->assertSuccessful();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'name' => 'new-name',
        ]);
    }

    public function test_edit_server_ip_address(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(UpdateServerInfo::class, [
            'server' => $this->server,
        ])
            ->fill([
                'name' => $this->server->name,
                'ip' => '2.2.2.2',
                'port' => $this->server->port,
            ])
            ->call('submit')
            ->assertSuccessful();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'ip' => '2.2.2.2',
            'status' => ServerStatus::READY,
        ]);
    }

    public function test_edit_server_ip_address_and_disconnect(): void
    {
        SSH::fake()->connectionWillFail();

        $this->actingAs($this->user);

        Livewire::test(UpdateServerInfo::class, [
            'server' => $this->server,
        ])
            ->fill([
                'name' => $this->server->name,
                'ip' => '2.2.2.2',
                'port' => 2222,
            ])
            ->call('submit')
            ->assertSuccessful();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'ip' => '2.2.2.2',
            'port' => 2222,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }

    public function test_check_updates(): void
    {
        SSH::fake('Available updates:10');

        $this->actingAs($this->user);

        Livewire::test(ServerDetails::class, [
            'server' => $this->server,
        ])
            ->callInfolistAction('last_updated_check', 'check-update')
            ->assertSuccessful()
            ->assertNotified(
                Notification::make()
                    ->info()
                    ->title('Available updates:')
                    ->body(9)
            );

        $this->server->refresh();
        $this->assertEquals(9, $this->server->updates);
    }

    public function test_update_server(): void
    {
        SSH::fake('Available updates:0');

        $this->actingAs($this->user);

        Livewire::test(ServerDetails::class, [
            'server' => $this->server,
        ])
            ->callInfolistAction('updates', 'update-server')
            ->assertSuccessful();

        $this->server->refresh();

        $this->assertEquals(ServerStatus::READY, $this->server->status);
        $this->assertEquals(0, $this->server->updates);
    }
}
