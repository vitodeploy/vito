<?php

use App\DNSProviders\AbstractDNSProvider;
use App\Models\DNSProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

class EditableTestDNSProvider extends AbstractDNSProvider
{
    public static function id(): string
    {
        return 'editable-test';
    }

    public function editableData(): array
    {
        return [
            'api_url' => $this->dnsProvider->credentials['api_url'] ?? '',
        ];
    }

    public function connect(array $credentials): bool
    {
        return true;
    }
}

test('authenticated user can view dns providers index', function () {
    $this->actingAs($this->user);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $response = $this->get(route('dns-providers'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dns-providers/index')
        ->has('dnsProviders.data', 1)
        ->where('dnsProviders.data.0.id', $dnsProvider->id)
        ->where('dnsProviders.data.0.name', $dnsProvider->name)
        ->where('dnsProviders.data.0.provider', $dnsProvider->provider)
        ->where('dnsProviders.data.0.connected', $dnsProvider->connected)
    );
});

test('dns providers index exposes editable data to the edit dialog', function () {
    $this->actingAs($this->user);

    config(['dns-provider.providers.editable-test' => [
        'label' => 'Editable Test',
        'handler' => EditableTestDNSProvider::class,
        'form' => [],
        'edit_form' => [],
    ]]);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => 'editable-test',
        'credentials' => ['api_url' => 'https://dns.example.com', 'token' => 'super-secret'],
    ]);

    $response = $this->get(route('dns-providers'));

    $response->assertSuccessful();
    $response->assertDontSee('super-secret');
    $response->assertInertia(fn ($page) => $page
        ->where('dnsProviders.data.0.id', $dnsProvider->id)
        ->where('dnsProviders.data.0.editable_data.api_url', 'https://dns.example.com')
        ->etc()
    );
});

test('dns providers index survives a provider with no registered handler', function () {
    $this->actingAs($this->user);

    DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => 'removed-plugin-provider',
    ]);

    $this->get(route('dns-providers'))
        ->assertSuccessful();
});

test('dns providers json survives a provider with no registered handler', function () {
    $this->actingAs($this->user);

    DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => 'removed-plugin-provider',
    ]);

    $this->get(route('dns-providers.json'))
        ->assertSuccessful();
});

test('authenticated user can view dns providers json', function () {
    $this->actingAs($this->user);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $response = $this->get(route('dns-providers.json'));

    $response->assertSuccessful();
    $response->assertJsonFragment([
        'id' => $dnsProvider->id,
        'name' => $dnsProvider->name,
        'provider' => $dnsProvider->provider,
        'connected' => $dnsProvider->connected,
        'project_id' => $dnsProvider->project_id,
        'global' => is_null($dnsProvider->project_id),
    ]);
});

test('authenticated user can create dns provider', function () {
    $this->actingAs($this->user);

    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => true,
            'result' => ['id' => 'test-user-id'],
        ], 200),
    ]);

    $data = [
        'name' => 'Test Cloudflare',
        'provider' => 'cloudflare',
        'token' => 'fake-token',
    ];

    $response = $this->post(route('dns-providers.store'), $data);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'DNS provider created.');

    $this->assertDatabaseHas('dns_providers', [
        'user_id' => $this->user->id,
        'name' => 'Test Cloudflare',
        'provider' => 'cloudflare',
        'project_id' => $this->user->current_project_id,
        'connected' => true,
    ]);
});

test('authenticated user can create global dns provider', function () {
    $this->actingAs($this->user);

    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => true,
            'result' => ['id' => 'test-user-id'],
        ], 200),
    ]);

    $data = [
        'name' => 'Global Cloudflare',
        'provider' => 'cloudflare',
        'token' => 'fake-token',
        'global' => true,
    ];

    $response = $this->post(route('dns-providers.store'), $data);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'DNS provider created.');

    $this->assertDatabaseHas('dns_providers', [
        'user_id' => $this->user->id,
        'name' => 'Global Cloudflare',
        'provider' => 'cloudflare',
        'project_id' => null,
        'connected' => true,
    ]);
});

test('dns provider creation requires name', function () {
    $this->actingAs($this->user);

    $data = [
        'provider' => 'cloudflare',
        'token' => 'fake-token',
    ];

    $response = $this->post(route('dns-providers.store'), $data);

    $response->assertSessionHasErrors(['name']);
});

test('dns provider creation requires valid provider', function () {
    $this->actingAs($this->user);

    $data = [
        'name' => 'Test Provider',
        'provider' => 'invalid-provider',
        'token' => 'fake-token',
    ];

    $response = $this->post(route('dns-providers.store'), $data);

    $response->assertSessionHasErrors(['provider']);
});

test('dns provider creation validates credentials', function () {
    $this->actingAs($this->user);

    $data = [
        'name' => 'Test Cloudflare',
        'provider' => 'cloudflare',
        // Missing required 'token' field
    ];

    $response = $this->post(route('dns-providers.store'), $data);

    $response->assertSessionHasErrors();
});

test('dns provider creation fails with invalid credentials', function () {
    $this->actingAs($this->user);

    Http::fake([
        '*' => Http::response([], 401),
    ]);

    $data = [
        'name' => 'Test Cloudflare',
        'provider' => 'cloudflare',
        'token' => 'invalid-token',
    ];

    $response = $this->post(route('dns-providers.store'), $data);

    $response->assertSessionHasErrors(['provider']);
});

test('authenticated user can update dns provider', function () {
    $this->actingAs($this->user);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $data = [
        'name' => 'Updated Name',
    ];

    $response = $this->patch(route('dns-providers.update', $dnsProvider), $data);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'DNS provider updated.');

    $this->assertDatabaseHas('dns_providers', [
        'id' => $dnsProvider->id,
        'name' => 'Updated Name',
        'connected' => true,
    ]);
});

test('dns provider update keeps credentials when empty', function () {
    $this->actingAs($this->user);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'credentials' => ['token' => 'original-token'],
    ]);

    $response = $this->patch(route('dns-providers.update', $dnsProvider), [
        'name' => 'Updated Name',
    ]);

    $response->assertRedirect();

    $dnsProvider->refresh();
    expect($dnsProvider->name)->toEqual('Updated Name');
    expect($dnsProvider->credentials)->toEqual(['token' => 'original-token']);
});

test('dns provider update changes credentials when provided', function () {
    $this->actingAs($this->user);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'credentials' => ['token' => 'original-token'],
    ]);

    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => true,
            'result' => [],
        ], 200),
    ]);

    $response = $this->patch(route('dns-providers.update', $dnsProvider), [
        'name' => 'Updated Name',
        'token' => 'new-token',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'DNS provider updated.');

    $dnsProvider->refresh();
    expect($dnsProvider->credentials)->toEqual(['token' => 'new-token']);
});

test('dns provider update rejects invalid credentials', function () {
    $this->actingAs($this->user);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'credentials' => ['token' => 'original-token'],
    ]);

    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => false,
            'errors' => [['message' => 'Invalid token']],
        ], 401),
    ]);

    $response = $this->patch(route('dns-providers.update', $dnsProvider), [
        'name' => 'Updated Name',
        'token' => 'bad-token',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('provider');

    $dnsProvider->refresh();
    expect($dnsProvider->credentials)->toEqual(['token' => 'original-token']);
});

test('dns provider update requires name', function () {
    $this->actingAs($this->user);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $data = [];

    $response = $this->patch(route('dns-providers.update', $dnsProvider), $data);

    $response->assertSessionHasErrors(['name']);
});

test('authenticated user can delete dns provider', function () {
    $this->actingAs($this->user);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->delete(route('dns-providers.destroy', $dnsProvider));

    $response->assertRedirect(route('dns-providers'));
    $response->assertSessionHas('success', 'DNS provider deleted.');

    $this->assertDatabaseMissing('dns_providers', [
        'id' => $dnsProvider->id,
    ]);
});

test('guest cannot access dns providers', function () {
    $response = $this->get(route('dns-providers'));

    $response->assertRedirect();
});

test('guest cannot create dns provider', function () {
    $data = [
        'name' => 'Test Provider',
        'provider' => 'cloudflare',
        'token' => 'fake-token',
    ];

    $response = $this->post(route('dns-providers.store'), $data);

    $response->assertRedirect();
});

test('guest cannot update dns provider', function () {
    $dnsProvider = DNSProvider::factory()->create();

    $data = [
        'name' => 'Updated Name',
    ];

    $response = $this->patch(route('dns-providers.update', $dnsProvider), $data);

    $response->assertRedirect();
});

test('guest cannot delete dns provider', function () {
    $dnsProvider = DNSProvider::factory()->create();

    $response = $this->delete(route('dns-providers.destroy', $dnsProvider));

    $response->assertRedirect();
});

test('user cannot view other users dns providers', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();

    DNSProvider::factory()->create([
        'user_id' => $otherUser->id,
        'project_id' => $otherUser->current_project_id,
    ]);

    $response = $this->get(route('dns-providers'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dns-providers/index')
        ->has('dnsProviders.data', 0)
    );
});

test('user cannot update other users dns provider', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $data = [
        'name' => 'Hacked Name',
    ];

    $response = $this->patch(route('dns-providers.update', $otherDnsProvider), $data);

    $response->assertForbidden();
});

test('user cannot delete other users dns provider', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->delete(route('dns-providers.destroy', $otherDnsProvider));

    $response->assertForbidden();
});

test('user can only see own dns providers in json', function () {
    $this->actingAs($this->user);

    $ownDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();

    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $otherUser->id,
        'project_id' => $otherUser->current_project_id,
    ]);

    $response = $this->get(route('dns-providers.json'));

    $response->assertSuccessful();
    $response->assertJsonFragment([
        'id' => $ownDnsProvider->id,
    ]);
    $response->assertJsonMissing([
        'id' => $otherDnsProvider->id,
    ]);
});

test('dns providers are filtered by project', function () {
    $this->actingAs($this->user);

    // Create DNS provider for current project
    $projectDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    // Create global DNS provider (no project)
    $globalDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => null,
    ]);

    // Create DNS provider for different project
    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();
    $otherProjectDnsProvider = DNSProvider::factory()->create([
        'user_id' => $otherUser->id,
        'project_id' => $otherUser->current_project_id,
    ]);

    $response = $this->get(route('dns-providers.json'));

    $response->assertSuccessful();
    $response->assertJsonFragment([
        'id' => $projectDnsProvider->id,
    ]);
    $response->assertJsonFragment([
        'id' => $globalDnsProvider->id,
    ]);
    $response->assertJsonMissing([
        'id' => $otherProjectDnsProvider->id,
    ]);
});

test('dns provider creation ignores user id manipulation', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();

    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => true,
            'result' => ['id' => 'test-user-id'],
        ], 200),
    ]);

    $data = [
        'name' => 'Test Provider',
        'provider' => 'cloudflare',
        'token' => 'fake-token',
        'user_id' => $otherUser->id, // Attempt to set different user
    ];

    $response = $this->post(route('dns-providers.store'), $data);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'DNS provider created.');

    $this->assertDatabaseHas('dns_providers', [
        'user_id' => $this->user->id, // Should be set to authenticated user
        'name' => 'Test Provider',
    ]);

    $this->assertDatabaseMissing('dns_providers', [
        'user_id' => $otherUser->id,
    ]);
});
