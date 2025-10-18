<?php

namespace Tests\Feature\API;

use App\Models\DNSProvider;
use App\Models\DNSRecord;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DomainsTest extends TestCase
{
    use RefreshDatabase;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->ensureHasDefaultProject();

        $this->otherUser = User::factory()->create();
        $this->otherUser->ensureHasDefaultProject();
    }

    public function test_authenticated_user_can_list_domains(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $response = $this->getJson('/api/domains');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'domain',
                        'dns_provider_id',
                        'metadata',
                        'dns_provider' => [
                            'id',
                            'name',
                            'provider',
                            'connected',
                            'project_id',
                            'global',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonFragment([
                'id' => $domain->id,
                'domain' => $domain->domain,
            ]);
    }

    public function test_unauthenticated_user_cannot_list_domains(): void
    {
        $response = $this->getJson('/api/domains');

        $response->assertUnauthorized();
    }

    public function test_user_without_read_ability_cannot_list_domains(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $response = $this->getJson('/api/domains');

        $response->assertForbidden();
    }

    public function test_user_can_only_see_their_own_domains(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        // Create domain for current user
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $userDomain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        // Create domain for other user
        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
        ]);

        $response = $this->getJson('/api/domains');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $userDomain->id]);
        $response->assertJsonMissing(['id' => $otherDomain->id]);
    }

    public function test_authenticated_user_can_create_domain(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Mock the DNS provider API call
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'result' => [
                    'id' => 'test-domain-id',
                    'name' => 'example.com',
                    'status' => 'active',
                    'created_on' => '2023-01-01T00:00:00Z',
                    'modified_on' => '2023-01-01T00:00:00Z',
                ],
                'success' => true,
            ], 200),
        ]);

        $domainData = [
            'dns_provider_id' => $dnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ];

        $response = $this->postJson('/api/domains', $domainData);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'domain',
                'dns_provider_id',
                'metadata',
                'dns_provider',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'dns_provider_id' => $dnsProvider->id,
            ]);

        $this->assertDatabaseHas('domains', [
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ]);
    }

    public function test_user_cannot_create_domain_with_other_users_dns_provider(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $domainData = [
            'dns_provider_id' => $otherDnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ];

        $response = $this->postJson('/api/domains', $domainData);

        $response->assertForbidden();
    }

    public function test_user_without_write_ability_cannot_create_domain(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domainData = [
            'dns_provider_id' => $dnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ];

        $response = $this->postJson('/api/domains', $domainData);

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_domain(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $response = $this->getJson("/api/domains/{$domain->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'domain',
                'dns_provider_id',
                'metadata',
                'dns_provider',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'id' => $domain->id,
                'domain' => $domain->domain,
            ]);
    }

    public function test_user_cannot_view_other_users_domain(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
        ]);

        $response = $this->getJson("/api/domains/{$otherDomain->id}");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_delete_domain(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $response = $this->deleteJson("/api/domains/{$domain->id}");

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Domain removed successfully']);

        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }

    public function test_user_cannot_delete_other_users_domain(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
        ]);

        $response = $this->deleteJson("/api/domains/{$otherDomain->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('domains', ['id' => $otherDomain->id]);
    }

    public function test_user_without_write_ability_cannot_delete_domain(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $response = $this->deleteJson("/api/domains/{$domain->id}");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_get_available_domains_from_dns_provider(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->getJson("/api/domains/{$dnsProvider->id}/available");

        $response->assertOk();
    }

    public function test_user_cannot_get_available_domains_from_other_users_dns_provider(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $response = $this->getJson("/api/domains/{$otherDnsProvider->id}/available");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_list_dns_records_for_domain(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $record1 = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ]);

        $record2 = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'CNAME',
            'name' => 'mail',
            'content' => 'example.com',
        ]);

        $response = $this->getJson("/api/domains/{$domain->id}/records");

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'type',
                    'name',
                    'formatted_name',
                    'content',
                    'ttl',
                    'formatted_ttl',
                    'proxied',
                    'domain_id',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonFragment([
                'id' => $record1->id,
                'type' => 'A',
                'name' => 'www',
            ])
            ->assertJsonFragment([
                'id' => $record2->id,
                'type' => 'CNAME',
                'name' => 'mail',
            ]);
    }

    public function test_user_cannot_list_dns_records_for_other_users_domain(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
        ]);

        $response = $this->getJson("/api/domains/{$otherDomain->id}/records");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_create_dns_record(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ]);

        // Mock the DNS provider API call
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'result' => [
                    'id' => 'test-record-id',
                    'type' => 'A',
                    'name' => 'www',
                    'content' => '192.168.1.1',
                    'ttl' => 300,
                    'proxied' => false,
                ],
                'success' => true,
            ], 200),
        ]);

        $recordData = [
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
            'ttl' => 300,
            'proxied' => false,
        ];

        $response = $this->postJson("/api/domains/{$domain->id}/records", $recordData);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'type',
                'name',
                'formatted_name',
                'content',
                'ttl',
                'formatted_ttl',
                'proxied',
                'domain_id',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'type' => 'A',
                'name' => 'www',
                'content' => '192.168.1.1',
            ]);

        $this->assertDatabaseHas('dns_records', [
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ]);
    }

    public function test_user_cannot_create_dns_record_for_other_users_domain(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
        ]);

        $recordData = [
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ];

        $response = $this->postJson("/api/domains/{$otherDomain->id}/records", $recordData);

        $response->assertForbidden();
    }

    public function test_user_without_write_ability_cannot_create_dns_record(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $recordData = [
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ];

        $response = $this->postJson("/api/domains/{$domain->id}/records", $recordData);

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_dns_record(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ]);

        $response = $this->getJson("/api/domains/{$domain->id}/records/{$record->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'type',
                'name',
                'formatted_name',
                'content',
                'ttl',
                'formatted_ttl',
                'proxied',
                'domain_id',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'id' => $record->id,
                'type' => 'A',
                'name' => 'www',
            ]);
    }

    public function test_user_cannot_view_dns_record_from_other_users_domain(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
        ]);

        $otherRecord = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $response = $this->getJson("/api/domains/{$otherDomain->id}/records/{$otherRecord->id}");

        $response->assertForbidden();
    }

    public function test_user_cannot_view_dns_record_that_does_not_belong_to_domain(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $response = $this->getJson("/api/domains/{$domain->id}/records/{$record->id}");

        $response->assertNotFound();
    }

    public function test_authenticated_user_can_update_dns_record(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
            'provider_record_id' => 'test-record-id',
        ]);

        // Mock the DNS provider API call
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'result' => [
                    'id' => 'test-record-id',
                    'type' => 'A',
                    'name' => 'www',
                    'content' => '192.168.1.2',
                    'ttl' => 600,
                    'proxied' => false,
                ],
                'success' => true,
            ], 200),
        ]);

        $updateData = [
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.2',
            'ttl' => 600,
        ];

        $response = $this->patchJson("/api/domains/{$domain->id}/records/{$record->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment([
                'content' => '192.168.1.2',
                'ttl' => 600,
            ]);

        $this->assertDatabaseHas('dns_records', [
            'id' => $record->id,
            'content' => '192.168.1.2',
            'ttl' => 600,
        ]);
    }

    public function test_user_cannot_update_dns_record_from_other_users_domain(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
        ]);

        $otherRecord = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $updateData = ['content' => '192.168.1.2'];

        $response = $this->patchJson("/api/domains/{$otherDomain->id}/records/{$otherRecord->id}", $updateData);

        $response->assertForbidden();
    }

    public function test_user_cannot_update_dns_record_that_does_not_belong_to_domain(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $updateData = ['content' => '192.168.1.2'];

        $response = $this->patchJson("/api/domains/{$domain->id}/records/{$record->id}", $updateData);

        $response->assertNotFound();
    }

    public function test_authenticated_user_can_delete_dns_record(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
        ]);

        $response = $this->deleteJson("/api/domains/{$domain->id}/records/{$record->id}");

        $response->assertOk()
            ->assertJsonFragment(['message' => 'DNS record deleted successfully']);

        $this->assertDatabaseMissing('dns_records', ['id' => $record->id]);
    }

    public function test_user_cannot_delete_dns_record_from_other_users_domain(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $this->otherUser->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
        ]);

        $otherRecord = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $response = $this->deleteJson("/api/domains/{$otherDomain->id}/records/{$otherRecord->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('dns_records', ['id' => $otherRecord->id]);
    }

    public function test_user_cannot_delete_dns_record_that_does_not_belong_to_domain(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $response = $this->deleteJson("/api/domains/{$domain->id}/records/{$record->id}");

        $response->assertNotFound();

        $this->assertDatabaseHas('dns_records', ['id' => $record->id]);
    }

    public function test_user_without_write_ability_cannot_delete_dns_record(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
        ]);

        $response = $this->deleteJson("/api/domains/{$domain->id}/records/{$record->id}");

        $response->assertForbidden();
    }

    // ==================== Edge Cases and Error Scenarios ====================

    public function test_domain_not_found_returns_404(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $response = $this->getJson('/api/domains/999999');

        $response->assertNotFound();
    }

    public function test_dns_record_not_found_returns_404(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $response = $this->getJson("/api/domains/{$domain->id}/records/999999");

        $response->assertNotFound();
    }

    public function test_dns_provider_not_found_returns_404(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $response = $this->getJson('/api/domains/999999/available');

        $response->assertNotFound();
    }

    public function test_domain_pagination_works_correctly(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Create 30 domains to test pagination
        Domain::factory()->count(30)->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        $response = $this->getJson('/api/domains');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);

        // Should have 25 items per page (as defined in controller)
        $this->assertCount(25, $response->json('data'));
    }

    public function test_dns_records_are_ordered_by_type_and_name(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
        ]);

        // Create records in random order
        DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'CNAME',
            'name' => 'zebra',
        ]);

        DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'alpha',
        ]);

        DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'beta',
        ]);

        DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'CNAME',
            'name' => 'alpha',
        ]);

        $response = $this->getJson("/api/domains/{$domain->id}/records");

        $response->assertOk();

        $records = $response->json();
        $this->assertCount(4, $records);

        // Should be ordered by type first (A before CNAME), then by name
        $this->assertEquals('A', $records[0]['type']);
        $this->assertEquals('alpha', $records[0]['name']);
        $this->assertEquals('A', $records[1]['type']);
        $this->assertEquals('beta', $records[1]['name']);
        $this->assertEquals('CNAME', $records[2]['type']);
        $this->assertEquals('alpha', $records[2]['name']);
        $this->assertEquals('CNAME', $records[3]['type']);
        $this->assertEquals('zebra', $records[3]['name']);
    }
}
