<?php

namespace Tests\Feature\API;

use App\DNSProviders\Cloudflare;
use App\Models\DNSProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DNSProvidersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_connect_provider(string $provider, array $input): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => ['id' => 'test-user-id'],
            ], 200),
        ]);

        $data = array_merge([
            'name' => 'Test DNS Provider',
            'provider' => $provider,
        ], $input);

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => $provider,
                'name' => 'Test DNS Provider',
                'connected' => true,
            ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_cannot_connect_to_provider(string $provider, array $input): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        Http::fake([
            '*' => Http::response([], 401),
        ]);

        $data = array_merge([
            'name' => 'Test DNS Provider',
            'provider' => $provider,
        ], $input);

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertJsonValidationErrorFor('provider');
    }

    public function test_see_dns_providers_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var DNSProvider $dnsProvider */
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $this->json('GET', route('api.dns-providers'))
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $dnsProvider->id,
                'provider' => $dnsProvider->provider,
                'name' => $dnsProvider->name,
            ]);
    }

    public function test_show_specific_dns_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var DNSProvider $dnsProvider */
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $this->json('GET', route('api.dns-providers.show', $dnsProvider))
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $dnsProvider->id,
                'provider' => $dnsProvider->provider,
                'name' => $dnsProvider->name,
                'connected' => $dnsProvider->connected,
            ]);
    }

    #[DataProvider('data')]
    public function test_delete_dns_provider(string $provider): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var DNSProvider $dnsProvider */
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => $provider,
        ]);

        $this->json('DELETE', route('api.dns-providers.destroy', $dnsProvider))
            ->assertSuccessful()
            ->assertJsonFragment([
                'message' => 'DNS provider deleted successfully',
            ]);
    }

    public function test_update_dns_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var DNSProvider $dnsProvider */
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $data = [
            'name' => 'Updated DNS Provider Name',
        ];

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $dnsProvider->id,
                'name' => 'Updated DNS Provider Name',
            ]);

        $dnsProvider->refresh();
        $this->assertEquals('Updated DNS Provider Name', $dnsProvider->name);
    }

    public function test_api_user_cannot_access_other_users_dns_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->json('GET', route('api.dns-providers.show', $dnsProvider))
            ->assertForbidden();
    }

    public function test_api_user_cannot_update_other_users_dns_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), [
            'name' => 'hacked',
        ])
            ->assertForbidden();
    }

    public function test_api_user_cannot_delete_other_users_dns_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->json('DELETE', route('api.dns-providers.destroy', $dnsProvider))
            ->assertForbidden();
    }

    public function test_api_guest_cannot_access_dns_providers(): void
    {
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->json('GET', route('api.dns-providers'))
            ->assertUnauthorized();

        $this->json('POST', route('api.dns-providers.create'), [])
            ->assertUnauthorized();

        $this->json('GET', route('api.dns-providers.show', $dnsProvider))
            ->assertUnauthorized();

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), [])
            ->assertUnauthorized();

        $this->json('DELETE', route('api.dns-providers.destroy', $dnsProvider))
            ->assertUnauthorized();
    }

    public function test_api_insufficient_scopes_denies_access(): void
    {
        Sanctum::actingAs($this->user, ['read']); // Only read scope

        $data = [
            'provider' => Cloudflare::id(),
            'name' => 'test',
            'token' => 'fake-token',
        ];

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertForbidden();
    }

    public function test_api_cannot_manipulate_user_id_on_creation(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();

        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => ['id' => 'test-user-id'],
            ], 200),
        ]);

        $data = [
            'provider' => Cloudflare::id(),
            'name' => 'test',
            'token' => 'fake-token',
            'user_id' => $otherUser->id,
        ];

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => Cloudflare::id(),
                'name' => 'test',
            ]);

        $this->assertDatabaseHas('dns_providers', [
            'name' => 'test',
            'provider' => Cloudflare::id(),
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseMissing('dns_providers', [
            'name' => 'test',
            'provider' => Cloudflare::id(),
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_api_user_can_only_see_own_dns_providers_in_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();

        $ownDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'own-dns-provider',
        ]);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'other-dns-provider',
        ]);

        $this->json('GET', route('api.dns-providers'))
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $ownDnsProvider->id,
                'provider' => $ownDnsProvider->provider,
            ])
            ->assertJsonMissing([
                'id' => $otherDnsProvider->id,
            ]);
    }

    public function test_dns_provider_creation_requires_name(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $data = [
            'provider' => Cloudflare::id(),
            'token' => 'fake-token',
        ];

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertJsonValidationErrorFor('name');
    }

    public function test_dns_provider_creation_requires_valid_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $data = [
            'name' => 'Test Provider',
            'provider' => 'invalid-provider',
            'token' => 'fake-token',
        ];

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertJsonValidationErrorFor('provider');
    }

    public function test_dns_provider_creation_validates_credentials(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $data = [
            'name' => 'Test Cloudflare',
            'provider' => Cloudflare::id(),
            // Missing required 'token' field
        ];

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_dns_provider_update_requires_name(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $data = [];

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), $data)
            ->assertJsonValidationErrorFor('name');
    }

    public function test_dns_provider_creation_with_global_flag(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => ['id' => 'test-user-id'],
            ], 200),
        ]);

        $data = [
            'name' => 'Global Cloudflare',
            'provider' => Cloudflare::id(),
            'token' => 'fake-token',
            'global' => true,
        ];

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => Cloudflare::id(),
                'name' => 'Global Cloudflare',
                'project_id' => null,
            ]);
    }

    public function test_dns_provider_creation_without_global_flag(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => ['id' => 'test-user-id'],
            ], 200),
        ]);

        $data = [
            'name' => 'Project Cloudflare',
            'provider' => Cloudflare::id(),
            'token' => 'fake-token',
        ];

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => Cloudflare::id(),
                'name' => 'Project Cloudflare',
                'project_id' => $this->user->current_project_id,
            ]);
    }

    public function test_dns_providers_are_filtered_by_project(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

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

        $response = $this->json('GET', route('api.dns-providers'))
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $projectDnsProvider->id,
            ])
            ->assertJsonFragment([
                'id' => $globalDnsProvider->id,
            ])
            ->assertJsonMissing([
                'id' => $otherProjectDnsProvider->id,
            ]);
    }

    public function test_dns_provider_creation_fails_with_invalid_credentials(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        Http::fake([
            '*' => Http::response([], 401),
        ]);

        $data = [
            'name' => 'Test Cloudflare',
            'provider' => Cloudflare::id(),
            'token' => 'invalid-token',
        ];

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertJsonValidationErrorFor('provider');
    }

    public function test_dns_provider_creation_ignores_user_id_manipulation(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();

        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => ['id' => 'test-user-id'],
            ], 200),
        ]);

        $data = [
            'name' => 'Test Provider',
            'provider' => Cloudflare::id(),
            'token' => 'fake-token',
            'user_id' => $otherUser->id, // Attempt to set different user
        ];

        $this->json('POST', route('api.dns-providers.create'), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => Cloudflare::id(),
                'name' => 'Test Provider',
            ]);

        $this->assertDatabaseHas('dns_providers', [
            'user_id' => $this->user->id, // Should be set to authenticated user
            'name' => 'Test Provider',
        ]);

        $this->assertDatabaseMissing('dns_providers', [
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_dns_provider_update_ignores_user_id_manipulation(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $data = [
            'name' => 'Updated Name',
            'user_id' => $otherUser->id, // Attempt to change user
        ];

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'Updated Name',
            ]);

        $dnsProvider->refresh();
        $this->assertEquals('Updated Name', $dnsProvider->name);
        $this->assertEquals($this->user->id, $dnsProvider->user_id); // Should remain unchanged
    }

    public function test_dns_provider_update_ignores_provider_manipulation(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Cloudflare::id(),
        ]);

        $data = [
            'name' => 'Updated Name',
            'provider' => 'different-provider', // Attempt to change provider
        ];

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'provider' => Cloudflare::id(), // Should remain unchanged
            ]);

        $dnsProvider->refresh();
        $this->assertEquals('Updated Name', $dnsProvider->name);
        $this->assertEquals(Cloudflare::id(), $dnsProvider->provider); // Should remain unchanged
    }

    public function test_dns_provider_update_keeps_credentials_when_empty(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'credentials' => ['token' => 'original-token'],
        ]);

        $data = [
            'name' => 'Updated Name',
        ];

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'Updated Name',
            ]);

        $dnsProvider->refresh();
        $this->assertEquals('Updated Name', $dnsProvider->name);
        $this->assertEquals(['token' => 'original-token'], $dnsProvider->credentials);
    }

    public function test_dns_provider_update_changes_credentials_when_provided(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'credentials' => ['token' => 'original-token'],
        ]);

        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => [],
            ], 200),
        ]);

        $data = [
            'name' => 'Updated Name',
            'token' => 'new-token',
        ];

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'Updated Name',
            ]);

        $dnsProvider->refresh();
        $this->assertEquals('Updated Name', $dnsProvider->name);
        $this->assertEquals(['token' => 'new-token'], $dnsProvider->credentials);
    }

    public function test_dns_provider_update_rejects_invalid_credentials(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'credentials' => ['token' => 'original-token'],
        ]);

        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => false,
                'errors' => [['message' => 'Invalid token']],
            ], 401),
        ]);

        $data = [
            'name' => 'Updated Name',
            'token' => 'bad-token',
        ];

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), $data)
            ->assertUnprocessable();

        $dnsProvider->refresh();
        $this->assertEquals(['token' => 'original-token'], $dnsProvider->credentials);
    }

    public function test_dns_provider_update_ignores_connected_manipulation(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'connected' => true,
        ]);

        $data = [
            'name' => 'Updated Name',
            'connected' => false, // Attempt to change connected status
        ];

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'connected' => true, // Should remain unchanged
            ]);

        $dnsProvider->refresh();
        $this->assertEquals('Updated Name', $dnsProvider->name);
        $this->assertTrue($dnsProvider->connected); // Should remain unchanged
    }

    public function test_dns_provider_update_ignores_project_id_manipulation(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $data = [
            'name' => 'Updated Name',
            'project_id' => 999, // Attempt to change project
        ];

        $this->json('PUT', route('api.dns-providers.update', $dnsProvider), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'project_id' => $this->user->current_project_id, // Should remain unchanged
            ]);

        $dnsProvider->refresh();
        $this->assertEquals('Updated Name', $dnsProvider->name);
        $this->assertEquals($this->user->current_project_id, $dnsProvider->project_id); // Should remain unchanged
    }

    /**
     * @return array<array<int, mixed>>
     */
    public static function data(): array
    {
        return [
            [Cloudflare::id(), ['token' => 'test-token']],
            [Cloudflare::id(), ['token' => 'test-token', 'global' => true]],
        ];
    }
}
