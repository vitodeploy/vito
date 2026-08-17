<?php

use App\Enums\UserRole;
use App\Models\DNSProvider;
use App\Models\Project;
use App\Models\ServerProvider;
use App\Models\SourceControl;
use App\Models\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    /** @var Project $outsideProject */
    $outsideProject = Project::factory()->create();
    $outsideProject->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $this->outsideProject = $outsideProject;
    $this->scopedProject = $this->user->currentProject;
});

function scopedToken(string $permission = 'read'): string
{
    $abilities = $permission === 'write'
        ? ['read', 'write', 'project:'.test()->scopedProject->id]
        : ['read', 'project:'.test()->scopedProject->id];

    return test()->user->createToken('scoped', $abilities)->plainTextToken;
}

test('scoped token does not list source controls from other projects', function (): void {
    $inside = SourceControl::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->scopedProject->id,
        'profile' => 'inside-profile',
    ]);
    $outside = SourceControl::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
        'profile' => 'outside-profile',
    ]);
    $global = SourceControl::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => null,
        'profile' => 'global-profile',
    ]);

    $this->withToken(scopedToken())
        ->json('GET', route('api.user.source-controls'))
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $inside->id])
        ->assertJsonFragment(['id' => $global->id])
        ->assertJsonMissing(['id' => $outside->id]);
});

test('scoped token does not list storage, server and dns providers from other projects', function (): void {
    $storage = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);
    $server = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);
    $dns = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);

    $token = scopedToken();

    $this->withToken($token)
        ->json('GET', route('api.user.storage-providers'))
        ->assertSuccessful()
        ->assertJsonMissing(['id' => $storage->id]);

    $this->withToken($token)
        ->json('GET', route('api.user.server-providers'))
        ->assertSuccessful()
        ->assertJsonMissing(['id' => $server->id]);

    $this->withToken($token)
        ->json('GET', route('api.dns-providers'))
        ->assertSuccessful()
        ->assertJsonMissing(['id' => $dns->id]);
});

test('scoped token cannot show a provider from another project', function (): void {
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);
    $dns = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);

    $token = scopedToken();

    $this->withToken($token)
        ->json('GET', route('api.user.source-controls.show', ['sourceControl' => $sourceControl->id]))
        ->assertForbidden();

    $this->withToken($token)
        ->json('GET', route('api.dns-providers.show', ['dnsProvider' => $dns->id]))
        ->assertForbidden();
});

test('scoped token cannot delete a provider from another project', function (): void {
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);
    $storage = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);
    $server = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);
    $dns = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);

    $token = scopedToken('write');

    $this->withToken($token)
        ->json('DELETE', route('api.user.source-controls.delete', ['sourceControl' => $sourceControl->id]))
        ->assertForbidden();

    $this->withToken($token)
        ->json('DELETE', route('api.user.storage-providers.delete', ['storageProvider' => $storage->id]))
        ->assertForbidden();

    $this->withToken($token)
        ->json('DELETE', route('api.user.server-providers.delete', ['serverProvider' => $server->id]))
        ->assertForbidden();

    $this->withToken($token)
        ->json('DELETE', route('api.dns-providers.destroy', ['dnsProvider' => $dns->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('source_controls', ['id' => $sourceControl->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('storage_providers', ['id' => $storage->id]);
    $this->assertDatabaseHas('server_providers', ['id' => $server->id]);
    $this->assertDatabaseHas('dns_providers', ['id' => $dns->id]);
});

test('scoped token cannot update a provider from another project', function (): void {
    $server = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
        'profile' => 'outside-profile',
    ]);

    $this->withToken(scopedToken('write'))
        ->json('PUT', route('api.user.server-providers.update', ['serverProvider' => $server->id]), [
            'name' => 'renamed',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('server_providers', [
        'id' => $server->id,
        'profile' => 'outside-profile',
    ]);
});

test('scoped token cannot write to a global provider', function (): void {
    $server = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => null,
        'profile' => 'global-profile',
    ]);

    $token = scopedToken('write');

    $this->withToken($token)
        ->json('PUT', route('api.user.server-providers.update', ['serverProvider' => $server->id]), [
            'name' => 'renamed',
        ])
        ->assertForbidden();

    $this->withToken($token)
        ->json('DELETE', route('api.user.server-providers.delete', ['serverProvider' => $server->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('server_providers', [
        'id' => $server->id,
        'profile' => 'global-profile',
    ]);
});

test('scoped token can read a global provider', function (): void {
    $server = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => null,
    ]);

    $this->withToken(scopedToken())
        ->json('GET', route('api.user.server-providers.show', ['serverProvider' => $server->id]))
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $server->id]);
});

test('scoped token cannot create a global provider', function (): void {
    Http::fake();

    $this->withToken(scopedToken('write'))
        ->json('POST', route('api.user.source-controls.create'), [
            'name' => 'new-global',
            'provider' => 'github',
            'token' => 'token',
            'global' => true,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('source_controls', [
        'profile' => 'new-global',
    ]);
});

test('scoped token cannot create a provider outside its projects', function (): void {
    Http::fake();

    $this->user->update(['current_project_id' => $this->outsideProject->id]);

    $this->withToken(scopedToken('write'))
        ->json('POST', route('api.user.source-controls.create'), [
            'name' => 'new-outside',
            'provider' => 'github',
            'token' => 'token',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('source_controls', [
        'profile' => 'new-outside',
    ]);
});

test('scoped token cannot move a provider out of its projects by going global', function (): void {
    $server = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->scopedProject->id,
    ]);

    $this->withToken(scopedToken('write'))
        ->json('PUT', route('api.user.server-providers.update', ['serverProvider' => $server->id]), [
            'name' => 'renamed',
            'global' => true,
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('server_providers', [
        'id' => $server->id,
        'project_id' => $this->scopedProject->id,
    ]);
});

test('scoped token cannot move a provider out of its projects by switching current project', function (): void {
    $server = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->scopedProject->id,
    ]);

    $this->user->update(['current_project_id' => $this->outsideProject->id]);

    $this->withToken(scopedToken('write'))
        ->json('PUT', route('api.user.server-providers.update', ['serverProvider' => $server->id]), [
            'name' => 'renamed',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('server_providers', [
        'id' => $server->id,
        'project_id' => $this->scopedProject->id,
    ]);
});

test('the deprecated project endpoint creates the provider in the requested project', function (): void {
    Http::fake();

    $this->user->update(['current_project_id' => $this->outsideProject->id]);

    $this->withToken(scopedToken('write'))
        ->json('POST', route('api.projects.source-controls.create', ['project' => $this->scopedProject->id]), [
            'name' => 'requested-project',
            'provider' => 'github',
            'token' => 'token',
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('source_controls', [
        'profile' => 'requested-project',
        'project_id' => $this->scopedProject->id,
    ]);
});

test('a non-boolean global flag cannot smuggle a provider out of scope', function (string $global): void {
    Http::fake();

    $this->withToken(scopedToken('write'))
        ->json('POST', route('api.user.source-controls.create'), [
            'name' => 'sneaky-global',
            'provider' => 'github',
            'token' => 'token',
            'global' => $global,
        ]);

    $this->assertDatabaseMissing('source_controls', [
        'profile' => 'sneaky-global',
        'project_id' => null,
    ]);
})->with(['false', 'off', 'no', '0', 'banana']);

test('scoped token can create a provider inside its own project', function (): void {
    Http::fake();

    $this->withToken(scopedToken('write'))
        ->json('POST', route('api.user.source-controls.create'), [
            'name' => 'in-scope',
            'provider' => 'github',
            'token' => 'token',
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('source_controls', [
        'profile' => 'in-scope',
        'project_id' => $this->scopedProject->id,
    ]);
});

test('scoped token can update and delete a provider inside its own project', function (): void {
    $server = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->scopedProject->id,
    ]);
    $dns = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->scopedProject->id,
    ]);

    $token = scopedToken('write');

    $this->withToken($token)
        ->json('PUT', route('api.user.server-providers.update', ['serverProvider' => $server->id]), [
            'name' => 'renamed',
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('server_providers', [
        'id' => $server->id,
        'profile' => 'renamed',
        'project_id' => $this->scopedProject->id,
    ]);

    $this->withToken($token)
        ->json('DELETE', route('api.dns-providers.destroy', ['dnsProvider' => $dns->id]))
        ->assertSuccessful();

    $this->assertDatabaseMissing('dns_providers', ['id' => $dns->id]);
});

test('scoped token can read a provider inside its own project', function (): void {
    $storage = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->scopedProject->id,
    ]);

    $this->withToken(scopedToken())
        ->json('GET', route('api.user.storage-providers.show', ['storageProvider' => $storage->id]))
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $storage->id]);
});

test('an unscoped token keeps full access to every project', function (): void {
    $outside = SourceControl::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->outsideProject->id,
    ]);

    $token = $this->user->createToken('full', ['read', 'write'])->plainTextToken;

    $this->withToken($token)
        ->json('GET', route('api.user.source-controls'))
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $outside->id]);

    $this->withToken($token)
        ->json('DELETE', route('api.user.source-controls.delete', ['sourceControl' => $outside->id]))
        ->assertSuccessful();
});
