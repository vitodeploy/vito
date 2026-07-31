<?php

use App\Actions\Server\Update;
use App\Enums\OperatingSystem;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Facades\Notifier;
use App\Facades\SSH;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerProvider;
use App\Models\User;
use App\NotificationChannels\Email\NotificationMail;
use App\Notifications\ServerAutoUpdateCompleted;
use App\ServerProviders\Custom;
use App\ServerProviders\Hetzner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('create server', function () {
    $this->actingAs($this->user);

    Storage::fake();
    SSH::fake('user_not_found');

    // fake output for vito user check and service installations
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
});

test('delete server', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $this->delete(route('servers.destroy', $this->server), [
        'name' => $this->server->name,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('servers', [
        'id' => $this->server->id,
    ]);
});

test('cannot delete on provider', function () {
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
});

test('delete server destroys provider vm when opted in', function () {
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
});

test('delete server keeps provider vm when opted out', function () {
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
});

test('delete server requires delete from provider for non custom', function () {
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
});

test('api delete server defaults to destroying provider vm', function () {
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
});

test('api delete server can opt out of provider destruction', function () {
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
});

test('check connection is ready', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->server->update(['status' => ServerStatus::DISCONNECTED]);

    $this->patch(route('servers.status', $this->server))
        ->assertSessionHas('success', 'Server status is '.ServerStatus::READY->getText());

    $this->assertDatabaseHas('servers', [
        'id' => $this->server->id,
        'status' => ServerStatus::READY,
    ]);
});

test('connection failed', function () {
    SSH::fake()->connectionWillFail();

    $this->actingAs($this->user);

    $this->server->update(['status' => ServerStatus::READY]);

    $this->patch(route('servers.status', $this->server))
        ->assertSessionHas('gray', 'Server status is '.ServerStatus::DISCONNECTED->getText());

    $this->assertDatabaseHas('servers', [
        'id' => $this->server->id,
        'status' => ServerStatus::DISCONNECTED,
    ]);
});

test('reboot server', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('servers.reboot', $this->server))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('servers', [
        'id' => $this->server->id,
        'status' => ServerStatus::DISCONNECTED,
    ]);
});

test('edit server', function () {
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
});

test('edit server ip address', function () {
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
});

test('edit server ip address and disconnect', function () {
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
});

test('check updates', function () {
    SSH::fake('Available updates:10');

    $this->actingAs($this->user);

    $this->post(route('servers.check-for-updates', $this->server))
        ->assertSessionDoesntHaveErrors();

    $this->server->refresh();
    expect($this->server->updates)->toEqual(10);
});

test('check updates splits kernel updates', function () {
    SSH::fake("Available updates:5\nKernel updates:2");

    $this->actingAs($this->user);

    $this->post(route('servers.check-for-updates', $this->server))
        ->assertSessionDoesntHaveErrors();

    $this->server->refresh();
    expect($this->server->updates)->toEqual(5);
    expect($this->server->kernel_updates)->toEqual(2);
});

test('kernel update warning is exposed', function () {
    $this->server->update(['kernel_updates' => 1]);

    $warnings = collect($this->server->getWarnings());

    expect($warnings->contains(fn (array $w): bool => $w['key'] === 'kernel_update_available' && $w['count'] === 1))->toBeTrue();
});

test('update server', function () {
    SSH::fake('Available updates:0');

    $this->actingAs($this->user);

    $this->post(route('servers.update', $this->server))
        ->assertSessionDoesntHaveErrors();

    $this->server->refresh();

    expect($this->server->status)->toEqual(ServerStatus::READY);
    expect($this->server->updates)->toEqual(0);
});

test('os upgrade parses markers', function () {
    SSH::fake("Packages upgraded:7\nReboot required:1");

    $result = $this->server->os()->upgrade();

    expect($result['upgraded'])->toBe(7);
    expect($result['reboot_required'])->toBeTrue();
});

test('auto update notifies when packages upgraded', function () {
    SSH::fake("Packages upgraded:3\nReboot required:1\nAvailable updates:0\nKernel updates:2");
    Notifier::spy();

    app(Update::class)->update($this->server, notify: true);

    Notifier::shouldHaveReceived('send')->withArgs(
        function (object $notifiable, object $notification): bool {
            if (! $notification instanceof ServerAutoUpdateCompleted) {
                return false;
            }

            $text = $notification->rawText();

            return str_contains($text, 'Packages upgraded: 3')
                && str_contains($text, 'Kernel updates available: 2')
                && str_contains($text, 'rebooted');
        }
    )->once();
});

test('manual update does not notify', function () {
    SSH::fake("Packages upgraded:3\nReboot required:1\nAvailable updates:0\nKernel updates:2");
    Notifier::spy();

    app(Update::class)->update($this->server);

    Notifier::shouldNotHaveReceived('send');
});

test('auto update is silent when nothing changed', function () {
    SSH::fake("Packages upgraded:0\nReboot required:0\nAvailable updates:0\nKernel updates:0");
    Notifier::spy();

    app(Update::class)->update($this->server, notify: true);

    Notifier::shouldNotHaveReceived('send');
});

test('update kernel', function () {
    SSH::fake("Available updates:0\nKernel updates:0");

    $this->actingAs($this->user);

    $this->post(route('servers.update-kernel', $this->server))
        ->assertSessionDoesntHaveErrors();

    $this->server->refresh();

    expect($this->server->status)->toEqual(ServerStatus::DISCONNECTED);
    expect($this->server->kernel_updates)->toEqual(0);
});

test('only owner can transfer server', function () {
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
});

test('transfer server', function () {
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

    expect($this->user->refresh()->current_project_id)->toEqual($newProject->id);
});

test('user role can view server', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    $this->actingAs($this->user);

    $this->get(route('servers.show', $this->server))
        ->assertOk();
});

test('admin role can view server', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::ADMIN,
    ]);

    $this->actingAs($this->user);

    $this->get(route('servers.show', $this->server))
        ->assertOk();
});

test('owner role can view server', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::OWNER,
    ]);

    $this->actingAs($this->user);

    $this->get(route('servers.show', $this->server))
        ->assertOk();
});

test('user role cannot create server', function () {
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
});

test('admin role can create server', function () {
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
});

test('owner role can create server', function () {
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
});

test('user role cannot update server', function () {
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
});

test('admin role can update server', function () {
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
});

test('owner role can update server', function () {
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
});

test('user role cannot delete server', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    SSH::fake();

    $this->actingAs($this->user);

    $this->delete(route('servers.destroy', $this->server), [
        'name' => $this->server->name,
    ])
        ->assertForbidden();
});

test('admin role cannot delete server', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::ADMIN,
    ]);

    SSH::fake();

    $this->actingAs($this->user);

    $this->delete(route('servers.destroy', $this->server), [
        'name' => $this->server->name,
    ])
        ->assertForbidden();
});

test('owner role can delete server', function () {
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
});

test('user role cannot manage server operations', function () {
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
});

test('admin role can manage server operations', function () {
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
});

test('owner role can manage server operations', function () {
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
});

test('cannot create server with unauthorized provider', function () {
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
});

test('cannot create server with ssh connection failure', function () {
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
});

test('cannot create server on vito server', function () {
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
});

test('can create server when host has unrelated vito user', function () {
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
});
