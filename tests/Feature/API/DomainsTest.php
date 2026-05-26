<?php

namespace Tests\Feature\API;

use App\Models\DNSProvider;
use App\Models\Domain;
use App\Models\Project;
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
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains");

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
        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains");

        $response->assertUnauthorized();
    }

    public function test_user_without_read_ability_cannot_list_domains(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains");

        $response->assertForbidden();
    }

    public function test_user_can_see_all_domains_in_their_project(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        // Create domain for current user in their project
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $userDomain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Create domain for other user in the SAME project
        $otherUserDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Create domain for other user in a DIFFERENT project
        $otherProject = Project::factory()->create();
        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        $otherProjectDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
            'project_id' => $otherProject->id,
        ]);

        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains");

        $response->assertOk();
        // Should see both domains from the same project, regardless of who created them
        $response->assertJsonFragment(['id' => $userDomain->id]);
        $response->assertJsonFragment(['id' => $otherUserDomain->id]);
        // Should NOT see domains from other projects
        $response->assertJsonMissing(['id' => $otherProjectDomain->id]);
    }

    public function test_user_can_access_domains_created_by_other_users_in_same_project(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        // Create a DNS provider for the current user's project
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Create a domain for another user in the same project
        $otherUserDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // User should be able to view the domain created by another user in the same project
        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/{$otherUserDomain->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $otherUserDomain->id,
                'domain' => $otherUserDomain->domain,
            ]);
    }

    public function test_authenticated_user_can_create_domain(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Mock the DNS provider API calls (getDomain + getRecords)
        Http::fake([
            'api.cloudflare.com/client/v4/zones/test-domain-id/dns_records*' => Http::response([
                'result' => [],
                'success' => true,
            ], 200),
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

        $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains", $domainData);

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

    public function test_create_domain_fails_when_record_sync_fails(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $callCount = 0;

        // First call (getDomain) succeeds, subsequent calls throw
        Http::fake(function () use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                return Http::response([
                    'result' => [
                        'id' => 'test-domain-id',
                        'name' => 'example.com',
                        'status' => 'active',
                        'created_on' => '2023-01-01T00:00:00Z',
                        'modified_on' => '2023-01-01T00:00:00Z',
                    ],
                    'success' => true,
                ], 200);
            }

            throw new \RuntimeException('Domain is not opted in to API access.');
        });

        $domainData = [
            'dns_provider_id' => $dnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ];

        $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains", $domainData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('domain');

        // Domain should not have been persisted due to transaction rollback
        $this->assertDatabaseMissing('domains', [
            'provider_domain_id' => 'test-domain-id',
        ]);
    }

    public function test_user_cannot_create_domain_with_dns_provider_from_other_project(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        // Create a different project for the other user
        $otherProject = Project::factory()->create();
        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        $domainData = [
            'dns_provider_id' => $otherDnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ];

        $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains", $domainData);

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

        $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains", $domainData);

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
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}");

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

    public function test_user_cannot_view_domains_from_other_projects(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        // Create a different project for the other user
        $otherProject = Project::factory()->create();
        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
            'project_id' => $otherProject->id,
        ]);

        $response = $this->getJson("/api/projects/{$otherProject->id}/domains/{$otherDomain->id}");

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
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->deleteJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}");

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Domain removed successfully']);

        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }

    public function test_user_cannot_delete_domains_from_other_projects(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        // Create a different project for the other user
        $otherProject = Project::factory()->create();
        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
            'project_id' => $otherProject->id,
        ]);

        $response = $this->deleteJson("/api/projects/{$otherProject->id}/domains/{$otherDomain->id}");

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
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->deleteJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_get_available_domains_from_dns_provider(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/{$dnsProvider->id}/available");

        $response->assertNotFound();
    }

    public function test_user_cannot_get_available_domains_from_dns_provider_in_other_project(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        // Create a different project for the other user
        $otherProject = Project::factory()->create();
        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        $response = $this->getJson("/api/projects/{$otherProject->id}/domains/{$otherDnsProvider->id}/available");

        $response->assertNotFound();
    }

    public function test_domain_not_found_returns_404(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/999999");

        $response->assertNotFound();
    }

    public function test_dns_provider_not_found_returns_404(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/999999/available");

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
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains");

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);

        // Should have 25 items per page (as defined in controller)
        $this->assertCount(25, $response->json('data'));
    }

    public function test_user_cannot_access_domains_from_other_projects(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        // Create a second project for a different user
        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();
        $otherProject = $otherUser->currentProject;

        // Create DNS provider for the other project
        $otherProjectDnsProvider = DNSProvider::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        // Create domain in the other project
        $otherProjectDomain = Domain::factory()->create([
            'user_id' => $otherUser->id,
            'dns_provider_id' => $otherProjectDnsProvider->id,
            'project_id' => $otherProject->id,
        ]);

        // Create domain in current project (before switching)
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $currentProjectDomain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Should only see domains from current project
        $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains");

        $response->assertOk();
        $response->assertJsonMissing(['id' => $otherProjectDomain->id]);
        $response->assertJsonFragment(['id' => $currentProjectDomain->id]); // Only domains from current project

        // Should not be able to access domain from other project
        $response = $this->getJson("/api/projects/{$otherProject->id}/domains/{$otherProjectDomain->id}");

        $response->assertForbidden();
    }

    public function test_user_cannot_create_domain_in_other_project(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        // Create a second project for a different user
        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();
        $otherProject = $otherUser->currentProject;

        // Create DNS provider for the other project
        $otherProjectDnsProvider = DNSProvider::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        // Try to create domain with DNS provider from other project
        $domainData = [
            'dns_provider_id' => $otherProjectDnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ];

        $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains", $domainData);

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_domain_from_other_project(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        // Create a second project for a different user
        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();
        $otherProject = $otherUser->currentProject;

        // Create DNS provider for the other project
        $otherProjectDnsProvider = DNSProvider::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        // Create domain in the other project
        $otherProjectDomain = Domain::factory()->create([
            'user_id' => $otherUser->id,
            'dns_provider_id' => $otherProjectDnsProvider->id,
            'project_id' => $otherProject->id,
        ]);

        // Try to delete domain from other project
        $response = $this->deleteJson("/api/projects/{$otherProject->id}/domains/{$otherProjectDomain->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('domains', ['id' => $otherProjectDomain->id]);
    }
}
