<?php

namespace Tests\Feature;

use App\Models\DNSProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DNSProvidersTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dns_providers_index(): void
    {
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
    }

    public function test_authenticated_user_can_view_dns_providers_json(): void
    {
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
    }

    public function test_authenticated_user_can_create_dns_provider(): void
    {
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
    }

    public function test_authenticated_user_can_create_global_dns_provider(): void
    {
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
    }

    public function test_dns_provider_creation_requires_name(): void
    {
        $this->actingAs($this->user);

        $data = [
            'provider' => 'cloudflare',
            'token' => 'fake-token',
        ];

        $response = $this->post(route('dns-providers.store'), $data);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_dns_provider_creation_requires_valid_provider(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Test Provider',
            'provider' => 'invalid-provider',
            'token' => 'fake-token',
        ];

        $response = $this->post(route('dns-providers.store'), $data);

        $response->assertSessionHasErrors(['provider']);
    }

    public function test_dns_provider_creation_validates_credentials(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Test Cloudflare',
            'provider' => 'cloudflare',
            // Missing required 'token' field
        ];

        $response = $this->post(route('dns-providers.store'), $data);

        $response->assertSessionHasErrors();
    }

    public function test_dns_provider_creation_fails_with_invalid_credentials(): void
    {
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
    }

    public function test_authenticated_user_can_update_dns_provider(): void
    {
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
    }

    public function test_dns_provider_update_keeps_credentials_when_empty(): void
    {
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
        $this->assertEquals('Updated Name', $dnsProvider->name);
        $this->assertEquals(['token' => 'original-token'], $dnsProvider->credentials);
    }

    public function test_dns_provider_update_changes_credentials_when_provided(): void
    {
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
        $this->assertEquals(['token' => 'new-token'], $dnsProvider->credentials);
    }

    public function test_dns_provider_update_rejects_invalid_credentials(): void
    {
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
        $this->assertEquals(['token' => 'original-token'], $dnsProvider->credentials);
    }

    public function test_dns_provider_update_requires_name(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $data = [];

        $response = $this->patch(route('dns-providers.update', $dnsProvider), $data);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_authenticated_user_can_delete_dns_provider(): void
    {
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
    }

    public function test_guest_cannot_access_dns_providers(): void
    {
        $response = $this->get(route('dns-providers'));

        $response->assertRedirect();
    }

    public function test_guest_cannot_create_dns_provider(): void
    {
        $data = [
            'name' => 'Test Provider',
            'provider' => 'cloudflare',
            'token' => 'fake-token',
        ];

        $response = $this->post(route('dns-providers.store'), $data);

        $response->assertRedirect();
    }

    public function test_guest_cannot_update_dns_provider(): void
    {
        $dnsProvider = DNSProvider::factory()->create();

        $data = [
            'name' => 'Updated Name',
        ];

        $response = $this->patch(route('dns-providers.update', $dnsProvider), $data);

        $response->assertRedirect();
    }

    public function test_guest_cannot_delete_dns_provider(): void
    {
        $dnsProvider = DNSProvider::factory()->create();

        $response = $this->delete(route('dns-providers.destroy', $dnsProvider));

        $response->assertRedirect();
    }

    public function test_user_cannot_view_other_users_dns_providers(): void
    {
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
    }

    public function test_user_cannot_update_other_users_dns_provider(): void
    {
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
    }

    public function test_user_cannot_delete_other_users_dns_provider(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->delete(route('dns-providers.destroy', $otherDnsProvider));

        $response->assertForbidden();
    }

    public function test_user_can_only_see_own_dns_providers_in_json(): void
    {
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
    }

    public function test_dns_providers_are_filtered_by_project(): void
    {
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
    }

    public function test_dns_provider_creation_ignores_user_id_manipulation(): void
    {
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
    }
}
