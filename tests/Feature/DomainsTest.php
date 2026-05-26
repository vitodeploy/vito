<?php

namespace Tests\Feature;

use App\Models\DNSProvider;
use App\Models\DNSRecord;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainsTest extends TestCase
{
    use RefreshDatabase;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for testing
        $this->user = User::factory()->create();
        $this->user->ensureHasDefaultProject();

        // Create a second user for authorization tests
        $this->otherUser = User::factory()->create();
        $this->otherUser->ensureHasDefaultProject();
    }

    public function test_authenticated_user_can_view_domains_index(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->get('/domains');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('domains/index')
                ->has('domains.data', 1)
                ->has('dnsProviders', 1)
                ->where('domains.data.0.id', $domain->id)
                ->where('dnsProviders.0.id', $dnsProvider->id)
            );
    }

    public function test_unauthenticated_user_cannot_view_domains_index(): void
    {
        $response = $this->get('/domains');

        $response->assertRedirect();
    }

    public function test_user_can_see_all_domains_in_their_current_project(): void
    {
        $this->actingAs($this->user);

        // Create a DNS provider for the current user's project
        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Create a domain for the current user in their project
        $userDomain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Create a domain for another user in the same project
        $otherUserDomain = Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // Create a domain for the other user in a different project
        $otherProject = Project::factory()->create();
        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $otherProject->id,
        ]);
        Domain::factory()->create([
            'user_id' => $this->otherUser->id,
            'dns_provider_id' => $otherDnsProvider->id,
            'project_id' => $otherProject->id,
        ]);

        $response = $this->get('/domains');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('domains/index')
                ->has('domains.data', 2)
                ->where('domains.data.0.id', $userDomain->id)
                ->where('domains.data.1.id', $otherUserDomain->id)
            );
    }

    public function test_user_can_access_domains_created_by_other_users_in_same_project(): void
    {
        $this->actingAs($this->user);

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
        $response = $this->get("/domains/{$otherUserDomain->id}");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('domains/show')
                ->where('domain.id', $otherUserDomain->id)
            );
    }

    public function test_authenticated_user_can_get_domains_json(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->get('/domains/json');

        $response->assertOk()
            ->assertJsonStructure([
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
            ])
            ->assertJsonFragment([
                'id' => $domain->id,
                'domain' => $domain->domain,
            ]);
    }

    public function test_unauthenticated_user_cannot_get_domains_json(): void
    {
        $response = $this->get('/domains/json');

        $response->assertRedirect();
    }

    public function test_authenticated_user_can_view_domain_show(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ]);

        $response = $this->get("/domains/{$domain->id}");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('domains/show')
                ->has('domain')
                ->has('records', 1)
                ->where('domain.id', $domain->id)
                ->where('records.0.id', $record->id)
            );
    }

    public function test_user_cannot_view_domains_from_other_projects(): void
    {
        $this->actingAs($this->user);

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

        $response = $this->get("/domains/{$otherDomain->id}");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_create_domain(): void
    {
        $this->actingAs($this->user);

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

        $response = $this->post('/domains', $domainData);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Domain added.');

        $this->assertDatabaseHas('domains', [
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'provider_domain_id' => 'test-domain-id',
        ]);
    }

    public function test_user_cannot_create_domain_with_dns_provider_from_other_project(): void
    {
        $this->actingAs($this->user);

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

        $response = $this->post('/domains', $domainData);

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_delete_domain(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->delete("/domains/{$domain->id}");

        $response->assertRedirectToRoute('domains')
            ->assertSessionHas('success', 'Domain removed.');

        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }

    public function test_user_cannot_delete_domains_from_other_projects(): void
    {
        $this->actingAs($this->user);

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

        $response = $this->delete("/domains/{$otherDomain->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('domains', ['id' => $otherDomain->id]);
    }

    public function test_authenticated_user_can_get_available_domains_from_dns_provider(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $response = $this->get("/domains/{$dnsProvider->id}/available");

        $response->assertOk();
    }

    public function test_user_cannot_get_available_domains_from_dns_provider_in_other_project(): void
    {
        $this->actingAs($this->user);

        // Create a different project for the other user
        $otherProject = Project::factory()->create();
        $otherDnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->otherUser->id,
            'project_id' => $otherProject->id,
        ]);

        $response = $this->get("/domains/{$otherDnsProvider->id}/available");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_dns_records_index(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ]);

        $response = $this->get("/domains/{$domain->id}/records");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('domain')
                ->has('records', 1)
                ->where('domain.id', $domain->id)
                ->where('records.0.id', $record->id)
            );
    }

    public function test_user_cannot_view_dns_records_for_domains_from_other_projects(): void
    {
        $this->actingAs($this->user);

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

        $response = $this->get("/domains/{$otherDomain->id}/records");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_get_dns_records_json(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
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

        $response = $this->get("/domains/{$domain->id}/records/json");

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'type',
                    'name',
                    'formatted_name',
                    'content',
                    'ttl',
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

    public function test_user_cannot_get_dns_records_json_for_domains_from_other_projects(): void
    {
        $this->actingAs($this->user);

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

        $response = $this->get("/domains/{$otherDomain->id}/records/json");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_create_dns_record(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
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

        $response = $this->post("/domains/{$domain->id}/records", $recordData);

        $response->assertRedirect()
            ->assertSessionHas('success', 'DNS record created.');

        $this->assertDatabaseHas('dns_records', [
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ]);
    }

    public function test_user_cannot_create_dns_record_for_domains_from_other_projects(): void
    {
        $this->actingAs($this->user);

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

        $recordData = [
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ];

        $response = $this->post("/domains/{$otherDomain->id}/records", $recordData);

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_update_dns_record(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
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

        $response = $this->patch("/domains/{$domain->id}/records/{$record->id}", $updateData);

        $response->assertRedirect()
            ->assertSessionHas('success', 'DNS record updated.');

        $this->assertDatabaseHas('dns_records', [
            'id' => $record->id,
            'content' => '192.168.1.2',
            'ttl' => 600,
        ]);
    }

    public function test_user_cannot_update_dns_record_from_domains_in_other_projects(): void
    {
        $this->actingAs($this->user);

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

        $otherRecord = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $updateData = ['content' => '192.168.1.2'];

        $response = $this->patch("/domains/{$otherDomain->id}/records/{$otherRecord->id}", $updateData);

        $response->assertForbidden();
    }

    public function test_user_cannot_update_dns_record_that_does_not_belong_to_domain(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $updateData = ['content' => '192.168.1.2'];

        $response = $this->patch("/domains/{$domain->id}/records/{$record->id}", $updateData);

        $response->assertNotFound();
    }

    public function test_authenticated_user_can_delete_dns_record(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
        ]);

        $response = $this->delete("/domains/{$domain->id}/records/{$record->id}");

        $response->assertRedirect()
            ->assertSessionHas('success', 'DNS record deleted.');

        $this->assertDatabaseMissing('dns_records', ['id' => $record->id]);
    }

    public function test_user_cannot_delete_dns_record_from_domains_in_other_projects(): void
    {
        $this->actingAs($this->user);

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

        $otherRecord = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $response = $this->delete("/domains/{$otherDomain->id}/records/{$otherRecord->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('dns_records', ['id' => $otherRecord->id]);
    }

    public function test_user_cannot_delete_dns_record_that_does_not_belong_to_domain(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $otherDomain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $record = DNSRecord::factory()->create([
            'domain_id' => $otherDomain->id,
        ]);

        $response = $this->delete("/domains/{$domain->id}/records/{$record->id}");

        $response->assertNotFound();

        $this->assertDatabaseHas('dns_records', ['id' => $record->id]);
    }

    public function test_domain_not_found_returns_404(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/domains/999999');

        $response->assertNotFound();
    }

    public function test_dns_record_show_route_does_not_exist(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
        ]);

        // There's no GET route for individual DNS records in the regular controller
        $response = $this->get("/domains/{$domain->id}/records/999999");

        $response->assertStatus(405); // Method not allowed
    }

    public function test_dns_provider_not_found_returns_404(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/domains/999999/available');

        $response->assertNotFound();
    }

    public function test_dns_records_are_ordered_by_type_and_name(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
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

        $response = $this->get("/domains/{$domain->id}/records/json");

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

    public function test_user_cannot_access_domains_from_other_projects(): void
    {
        $this->actingAs($this->user);

        // Create a second project for a different user (not the current user)
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
        $response = $this->get('/domains');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('domains/index')
                ->has('domains.data', 1) // Only domains from current project
                ->where('domains.data.0.id', $currentProjectDomain->id)
            );

        // Should not be able to access domain from other project
        $response = $this->get("/domains/{$otherProjectDomain->id}");

        $response->assertForbidden();
    }

    public function test_user_cannot_create_domain_in_other_project(): void
    {
        $this->actingAs($this->user);

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

        $response = $this->post('/domains', $domainData);

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_domain_from_other_project(): void
    {
        $this->actingAs($this->user);

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
        $response = $this->delete("/domains/{$otherProjectDomain->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('domains', ['id' => $otherProjectDomain->id]);
    }

    public function test_authenticated_user_can_sync_dns_records(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
            'provider_domain_id' => 'test-domain-id',
        ]);

        // Create an existing record that will be replaced
        DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'old',
            'content' => '192.168.1.1',
        ]);

        // Mock the DNS provider API call to return records
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'result' => [
                    [
                        'id' => 'record-1',
                        'type' => 'A',
                        'name' => 'www',
                        'content' => '192.168.1.1',
                        'ttl' => 300,
                        'proxied' => false,
                        'created_on' => '2023-01-01T00:00:00Z',
                        'modified_on' => '2023-01-01T00:00:00Z',
                    ],
                    [
                        'id' => 'record-2',
                        'type' => 'CNAME',
                        'name' => 'mail',
                        'content' => 'example.com',
                        'ttl' => 600,
                        'proxied' => false,
                        'created_on' => '2023-01-01T00:00:00Z',
                        'modified_on' => '2023-01-01T00:00:00Z',
                    ],
                ],
                'success' => true,
            ], 200),
        ]);

        $response = $this->post("/domains/{$domain->id}/records/sync");

        $response->assertRedirect()
            ->assertSessionHas('success', 'DNS records synced successfully.');

        // Check that old record was deleted and new records were created
        $this->assertDatabaseMissing('dns_records', [
            'domain_id' => $domain->id,
            'name' => 'old',
        ]);

        $this->assertDatabaseHas('dns_records', [
            'domain_id' => $domain->id,
            'provider_record_id' => 'record-1',
            'type' => 'A',
            'name' => 'www',
            'content' => '192.168.1.1',
        ]);

        $this->assertDatabaseHas('dns_records', [
            'domain_id' => $domain->id,
            'provider_record_id' => 'record-2',
            'type' => 'CNAME',
            'name' => 'mail',
            'content' => 'example.com',
        ]);
    }

    public function test_sync_dns_records_returns_error_when_provider_fails(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'dns_provider_id' => $dnsProvider->id,
            'project_id' => $this->user->current_project_id,
            'provider_domain_id' => 'test-domain-id',
        ]);

        $existingRecord = DNSRecord::factory()->create([
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => 'existing',
            'content' => '192.168.1.1',
        ]);

        // Mock the DNS provider API call to throw an exception
        Http::fake(function () {
            throw new \RuntimeException('Domain is not opted in to API access.');
        });

        $response = $this->post("/domains/{$domain->id}/records/sync");

        $response->assertRedirect()
            ->assertSessionHas('error');

        // Existing record should remain intact after a failed sync
        $this->assertDatabaseHas('dns_records', [
            'id' => $existingRecord->id,
            'domain_id' => $domain->id,
            'name' => 'existing',
        ]);
    }

    public function test_add_domain_fails_when_record_sync_fails(): void
    {
        $this->actingAs($this->user);

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

        $response = $this->post('/domains', $domainData);

        $response->assertRedirect()
            ->assertSessionHasErrors('domain');

        // Domain should not have been persisted due to transaction rollback
        $this->assertDatabaseMissing('domains', [
            'provider_domain_id' => 'test-domain-id',
        ]);
    }

    public function test_user_cannot_sync_dns_records_for_domains_from_other_projects(): void
    {
        $this->actingAs($this->user);

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

        $response = $this->post("/domains/{$otherDomain->id}/records/sync");

        $response->assertForbidden();
    }

    public function test_available_domains_returns_cached_value_when_cache_exists(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $cachedDomains = [
            ['id' => 'cached-zone-1', 'name' => 'cached.com', 'status' => 'active'],
        ];

        Cache::put("dns_provider_{$dnsProvider->id}_domains", $cachedDomains, 3600);

        // Should NOT make an API call — Http::fake with no matching routes would throw if called
        Http::fake([]);

        $response = $this->get("/domains/{$dnsProvider->id}/available");

        $response->assertOk();
        $this->assertEquals($cachedDomains, $response->json());
    }

    public function test_refresh_domains_skips_cache_and_fetches_from_provider(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $staleDomains = [
            ['id' => 'stale-zone-1', 'name' => 'stale.com', 'status' => 'active'],
        ];

        Cache::put("dns_provider_{$dnsProvider->id}_domains", $staleDomains, 3600);

        $freshDomains = [
            ['id' => 'zone-1', 'name' => 'fresh.com', 'status' => 'active', 'created_on' => '2023-01-01', 'modified_on' => '2023-01-02'],
            ['id' => 'zone-2', 'name' => 'new.com', 'status' => 'active', 'created_on' => '2023-01-03', 'modified_on' => '2023-01-04'],
        ];

        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'success' => true,
                'result' => $freshDomains,
            ], 200),
        ]);

        $response = $this->get("/domains/{$dnsProvider->id}/refresh");

        $response->assertOk();

        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertEquals('fresh.com', $data[0]['name']);
        $this->assertEquals('new.com', $data[1]['name']);

        // Cache should now be updated with fresh data
        $cached = Cache::get("dns_provider_{$dnsProvider->id}_domains");
        $this->assertCount(2, $cached);
        $this->assertEquals('fresh.com', $cached[0]['name']);
    }

    public function test_refresh_domains_updates_cache_for_subsequent_available_calls(): void
    {
        $this->actingAs($this->user);

        $dnsProvider = DNSProvider::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $freshDomains = [
            ['id' => 'zone-1', 'name' => 'example.com', 'status' => 'active', 'created_on' => '2023-01-01', 'modified_on' => '2023-01-02'],
        ];

        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'success' => true,
                'result' => $freshDomains,
            ], 200),
        ]);

        // First call: refresh to populate cache
        $this->get("/domains/{$dnsProvider->id}/refresh")->assertOk();

        // Second call: available should use cache (no API call needed)
        Http::fake([]);

        $response = $this->get("/domains/{$dnsProvider->id}/available");

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('example.com', $response->json()[0]['name']);
    }
}
