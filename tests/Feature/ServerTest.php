<?php

namespace Tests\Feature;

use App\Enums\OperatingSystem;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerProvider;
use App\Models\User;
use App\NotificationChannels\Email\NotificationMail;
use App\ServerProviders\Custom;
use App\ServerProviders\Hetzner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_server(): void
    {
        $this->actingAs($this->user);

        Storage::fake();
        SSH::fake('user_not_found'); // fake output for vito user check and service installations

        $this->post(route('servers.store', [
            'provider' => Custom::id(),
            'name' => 'test',
            'ip' => '1.1.1.1',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22->value,
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
            'delete_from_provider' => true,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('servers', [
            'id' => $this->server->id,
        ]);

        Mail::assertSent(NotificationMail::class);
    }

    public function test_delete_server_destroys_provider_vm_when_opted_in(): void
    {
        Http::fake();

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
                'hetzner_id' => 42,
                'ssh_key_id' => 1,
            ],
        ]);

        $this->delete(route('servers.destroy', $this->server), [
            'name' => $this->server->name,
            'delete_from_provider' => true,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('servers', [
            'id' => $this->server->id,
        ]);

        Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
            && str_contains($request->url(), '/servers/42'));
    }

    public function test_delete_server_keeps_provider_vm_when_opted_out(): void
    {
        Http::fake();

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
                'hetzner_id' => 42,
                'ssh_key_id' => 1,
            ],
        ]);

        $this->delete(route('servers.destroy', $this->server), [
            'name' => $this->server->name,
            'delete_from_provider' => false,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('servers', [
            'id' => $this->server->id,
        ]);

        Http::assertNothingSent();
    }

    public function test_delete_server_requires_delete_from_provider_for_non_custom(): void
    {
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
                'hetzner_id' => 42,
                'ssh_key_id' => 1,
            ],
        ]);

        $this->delete(route('servers.destroy', $this->server), [
            'name' => $this->server->name,
        ])
            ->assertSessionHasErrors('delete_from_provider');

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
        ]);
    }

    public function test_api_delete_server_defaults_to_destroying_provider_vm(): void
    {
        Http::fake();

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
                'hetzner_id' => 99,
                'ssh_key_id' => 1,
            ],
        ]);

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->deleteJson(route('api.projects.servers.delete', [
            'project' => $this->server->project_id,
            'server' => $this->server->id,
        ]))
            ->assertNoContent();

        $this->assertDatabaseMissing('servers', [
            'id' => $this->server->id,
        ]);

        Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
            && str_contains($request->url(), '/servers/99'));
    }

    public function test_api_delete_server_can_opt_out_of_provider_destruction(): void
    {
        Http::fake();

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
                'hetzner_id' => 99,
                'ssh_key_id' => 1,
            ],
        ]);

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->deleteJson(route('api.projects.servers.delete', [
            'project' => $this->server->project_id,
            'server' => $this->server->id,
        ]), ['delete_from_provider' => false])
            ->assertNoContent();

        $this->assertDatabaseMissing('servers', [
            'id' => $this->server->id,
        ]);

        Http::assertNothingSent();
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
        $this->assertEquals(10, $this->server->updates);
    }

    public function test_check_updates_splits_kernel_updates(): void
    {
        SSH::fake("Available updates:5\nKernel updates:2");

        $this->actingAs($this->user);

        $this->post(route('servers.check-for-updates', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->server->refresh();
        $this->assertEquals(5, $this->server->updates);
        $this->assertEquals(2, $this->server->kernel_updates);
    }

    public function test_kernel_update_warning_is_exposed(): void
    {
        $this->server->update(['kernel_updates' => 1]);

        $warnings = collect($this->server->getWarnings());

        $this->assertTrue($warnings->contains(fn (array $w): bool => $w['key'] === 'kernel_update_available' && $w['count'] === 1));
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

    public function test_update_kernel(): void
    {
        SSH::fake("Available updates:0\nKernel updates:0");

        $this->actingAs($this->user);

        $this->post(route('servers.update-kernel', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->server->refresh();

        $this->assertEquals(ServerStatus::DISCONNECTED, $this->server->status);
        $this->assertEquals(0, $this->server->kernel_updates);
    }

    public function test_only_owner_can_transfer_server(): void
    {
        $this->actingAs($this->user);

        $oldProject = $this->server->project;
        $oldProject->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::ADMIN,
        ]);

        /** @var Project $newProject */
        $newProject = $this->user->projects()->create([
            'name' => 'New Project',
        ]);
        $newProject->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::OWNER,
        ]);

        $this->post(route('servers.transfer', $this->server), [
            'project_id' => $newProject->id,
        ])
            ->assertForbidden();
    }

    public function test_transfer_server(): void
    {
        $this->actingAs($this->user);

        $oldProject = $this->server->project;
        $oldProject->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::OWNER,
        ]);

        /** @var Project $newProject */
        $newProject = $this->user->projects()->create([
            'name' => 'New Project',
        ]);
        $newProject->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::OWNER,
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

    public function test_user_role_can_view_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        $this->actingAs($this->user);

        $this->get(route('servers.show', $this->server))
            ->assertOk();
    }

    public function test_admin_role_can_view_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::ADMIN,
        ]);

        $this->actingAs($this->user);

        $this->get(route('servers.show', $this->server))
            ->assertOk();
    }

    public function test_owner_role_can_view_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::OWNER,
        ]);

        $this->actingAs($this->user);

        $this->get(route('servers.show', $this->server))
            ->assertOk();
    }

    public function test_user_role_cannot_create_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        $this->actingAs($this->user);

        Storage::fake();
        SSH::fake('user_not_found');

        $this->post(route('servers.store'), [
            'provider' => Custom::id(),
            'name' => 'test-user-server',
            'ip' => '2.2.2.2',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22->value,
            'services' => [
                [
                    'name' => 'ufw',
                    'type' => 'firewall',
                    'version' => 'latest',
                ],
            ],
        ])
            ->assertForbidden();
    }

    public function test_admin_role_can_create_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::ADMIN,
        ]);

        $this->actingAs($this->user);

        Storage::fake();
        SSH::fake('user_not_found');

        $this->post(route('servers.store'), [
            'provider' => Custom::id(),
            'name' => 'test-admin-server',
            'ip' => '3.3.3.3',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22->value,
            'services' => [
                [
                    'name' => 'ufw',
                    'type' => 'firewall',
                    'version' => 'latest',
                ],
            ],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'name' => 'test-admin-server',
            'ip' => '3.3.3.3',
        ]);
    }

    public function test_owner_role_can_create_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::OWNER,
        ]);

        $this->actingAs($this->user);

        Storage::fake();
        SSH::fake('user_not_found');

        $this->post(route('servers.store'), [
            'provider' => Custom::id(),
            'name' => 'test-owner-server',
            'ip' => '4.4.4.4',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22->value,
            'services' => [
                [
                    'name' => 'ufw',
                    'type' => 'firewall',
                    'version' => 'latest',
                ],
            ],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'name' => 'test-owner-server',
            'ip' => '4.4.4.4',
        ]);
    }

    public function test_user_role_cannot_update_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        SSH::fake();

        $this->actingAs($this->user);

        $this->patch(route('server-settings.update', $this->server), [
            'name' => 'new-name',
            'ip' => $this->server->ip,
            'port' => $this->server->port,
        ])
            ->assertForbidden();
    }

    public function test_admin_role_can_update_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::ADMIN,
        ]);

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

    public function test_owner_role_can_update_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::OWNER,
        ]);

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

    public function test_user_role_cannot_delete_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        SSH::fake();

        $this->actingAs($this->user);

        $this->delete(route('servers.destroy', $this->server), [
            'name' => $this->server->name,
        ])
            ->assertForbidden();
    }

    public function test_admin_role_cannot_delete_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::ADMIN,
        ]);

        SSH::fake();

        $this->actingAs($this->user);

        $this->delete(route('servers.destroy', $this->server), [
            'name' => $this->server->name,
        ])
            ->assertForbidden();
    }

    public function test_owner_role_can_delete_server(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::OWNER,
        ]);

        SSH::fake();

        $this->actingAs($this->user);

        $this->delete(route('servers.destroy', $this->server), [
            'name' => $this->server->name,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('servers', [
            'id' => $this->server->id,
        ]);
    }

    public function test_user_role_cannot_manage_server_operations(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        SSH::fake();

        $this->actingAs($this->user);

        // Test reboot
        $this->post(route('servers.reboot', $this->server))
            ->assertForbidden();

        // Test check updates
        $this->post(route('servers.check-for-updates', $this->server))
            ->assertForbidden();

        // Test update server
        $this->post(route('servers.update', $this->server))
            ->assertForbidden();

        // Test update kernel
        $this->post(route('servers.update-kernel', $this->server))
            ->assertForbidden();
    }

    public function test_admin_role_can_manage_server_operations(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::ADMIN,
        ]);

        SSH::fake('Available updates:10');

        $this->actingAs($this->user);

        // Test reboot
        $this->post(route('servers.reboot', $this->server))
            ->assertSessionDoesntHaveErrors();

        // Reset server status for next test
        $this->server->update(['status' => ServerStatus::READY]);

        // Test check updates
        $this->post(route('servers.check-for-updates', $this->server))
            ->assertSessionDoesntHaveErrors();

        // Test update server
        SSH::fake('Available updates:0');
        $this->post(route('servers.update', $this->server))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_owner_role_can_manage_server_operations(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::OWNER,
        ]);

        SSH::fake('Available updates:10');

        $this->actingAs($this->user);

        // Test reboot
        $this->post(route('servers.reboot', $this->server))
            ->assertSessionDoesntHaveErrors();

        // Reset server status for next test
        $this->server->update(['status' => ServerStatus::READY]);

        // Test check updates
        $this->post(route('servers.check-for-updates', $this->server))
            ->assertSessionDoesntHaveErrors();

        // Test update server
        SSH::fake('Available updates:0');
        $this->post(route('servers.update', $this->server))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_cannot_create_server_with_unauthorized_provider(): void
    {
        $this->actingAs($this->user);

        // Create a server provider that belongs to a different user
        $otherUser = User::factory()->create();
        $unauthorizedProvider = ServerProvider::factory()->create([
            'user_id' => $otherUser->id,
            'provider' => Hetzner::id(),
            'credentials' => ['token' => 'test-token'],
        ]);

        Storage::fake();
        SSH::fake('user_not_found');

        $this->post(route('servers.store'), [
            'provider' => Hetzner::id(),
            'server_provider' => $unauthorizedProvider->id,
            'name' => 'test-unauthorized-server',
            'os' => OperatingSystem::UBUNTU22->value,
            'plan' => 'cx11',
            'region' => 'nbg1',
            'services' => [
                [
                    'name' => 'ufw',
                    'type' => 'firewall',
                    'version' => 'latest',
                ],
            ],
        ])
            ->assertStatus(403)
            ->assertSee('You do not have permission to use this server provider.');

        $this->assertDatabaseMissing('servers', [
            'name' => 'test-unauthorized-server',
        ]);
    }

    public function test_cannot_create_server_with_ssh_connection_failure(): void
    {
        $this->actingAs($this->user);

        Storage::fake();
        SSH::fake()->connectionWillFail();

        $this->post(route('servers.store'), [
            'provider' => Custom::id(),
            'name' => 'test-connection-fail',
            'ip' => '5.5.5.5',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22->value,
            'services' => [],
        ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('servers', [
            'name' => 'test-connection-fail',
        ]);
    }

    public function test_cannot_create_server_on_vito_server(): void
    {
        $this->actingAs($this->user);

        Storage::fake();
        SSH::fake('vito_managed_host');

        $this->post(route('servers.store'), [
            'provider' => Custom::id(),
            'name' => 'test-vito-server',
            'ip' => '6.6.6.6',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22->value,
            'services' => [],
        ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('servers', [
            'name' => 'test-vito-server',
        ]);
    }

    public function test_can_create_server_when_host_has_unrelated_vito_user(): void
    {
        $this->actingAs($this->user);

        Storage::fake();
        SSH::fake('ok');

        $this->post(route('servers.store'), [
            'provider' => Custom::id(),
            'name' => 'unrelated-vito-user',
            'ip' => '7.7.7.7',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22->value,
            'services' => [],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'name' => 'unrelated-vito-user',
            'ip' => '7.7.7.7',
        ]);
    }
}
