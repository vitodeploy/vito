<?php

namespace Tests\Feature;

use App\Enums\OperatingSystem;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerProvider;
use App\NotificationChannels\Email\NotificationMail;
use App\ServerProviders\Custom;
use App\ServerProviders\Hetzner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_server(): void
    {
        $this->actingAs($this->user);

        Storage::fake();
        SSH::fake('Active: active'); // fake output for service installations

        $this->post(route('servers.store', [
            'provider' => Custom::id(),
            'name' => 'test',
            'ip' => '1.1.1.1',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22,
            'services' => [
                [
                    'name' => 'ufw',
                    'type' => 'firewall',
                    'version' => 'latest',
                ],
            ],
        ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'name' => 'test',
            'ip' => '1.1.1.1',
            'status' => ServerStatus::READY,
        ]);

        /** @var Server $server */
        $server = Server::where('name', 'test')->where('ip', '1.1.1.1')->first();

        $this->assertDatabaseHas('services', [
            'server_id' => $server->id,
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

        $this->delete(route('servers.destroy', $this->server), [
            'name' => $this->server->name,
        ])
            ->assertSessionDoesntHaveErrors();

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

        $provider = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Hetzner::id(),
            'credentials' => [
                'token' => 'token',
            ],
        ]);

        $this->server->update([
            'provider' => Hetzner::id(),
            'provider_id' => $provider->id,
            'provider_data' => [
                'hetzner_id' => 1,
                'ssh_key_id' => 1,
            ],
        ]);

        $this->delete(route('servers.destroy', $this->server), [
            'name' => $this->server->name,
        ])
            ->assertSessionDoesntHaveErrors();

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

        $this->patch(route('servers.status', $this->server))
            ->assertSessionHas('success', 'Server status is '.ServerStatus::READY->getText());

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

        $this->patch(route('servers.status', $this->server))
            ->assertSessionHas('gray', 'Server status is '.ServerStatus::DISCONNECTED->getText());

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }

    public function test_reboot_server(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.reboot', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }

    public function test_edit_server(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('server-settings.update', $this->server), [
            'name' => 'new-name',
            'ip' => $this->server->ip,
            'port' => $this->server->port,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'name' => 'new-name',
        ]);
    }

    public function test_edit_server_ip_address(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('server-settings.update', $this->server), [
            'name' => $this->server->name,
            'ip' => '2.2.2.2',
            'port' => $this->server->port,
        ])
            ->assertSessionDoesntHaveErrors();

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

        $this->patch(route('server-settings.update', $this->server), [
            'name' => $this->server->name,
            'ip' => '2.2.2.2',
            'port' => 2222,
        ])
            ->assertSessionDoesntHaveErrors();

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

        $this->post(route('servers.check-for-updates', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->server->refresh();
        $this->assertEquals(9, $this->server->updates);
    }

    public function test_update_server(): void
    {
        SSH::fake('Available updates:0');

        $this->actingAs($this->user);

        $this->post(route('servers.update', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->server->refresh();

        $this->assertEquals(ServerStatus::READY, $this->server->status);
        $this->assertEquals(0, $this->server->updates);
    }

    public function test_transfer_server(): void
    {
        $this->actingAs($this->user);

        /** @var Project $newProject */
        $newProject = $this->user->projects()->create([
            'name' => 'New Project',
        ]);

        $this->post(route('servers.transfer', $this->server), [
            'project_id' => $newProject->id,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'project_id' => $newProject->id,
        ]);

        $this->assertEquals($newProject->id, $this->user->refresh()->current_project_id);
    }
}
