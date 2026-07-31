<?php

use App\Models\DNSProvider;
use App\Models\DNSRecord;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user for testing
    $this->user = User::factory()->create();
    $this->user->ensureHasDefaultProject();

    // Create a second user for authorization tests
    $this->otherUser = User::factory()->create();
    $this->otherUser->ensureHasDefaultProject();
});

test('authenticated user can view domains index', function () {
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
});

test('unauthenticated user cannot view domains index', function () {
    $response = $this->get('/domains');

    $response->assertRedirect();
});

test('user can see all domains in their current project', function () {
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
});

test('user can access domains created by other users in same project', function () {
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
});

test('authenticated user can get domains json', function () {
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
});

test('unauthenticated user cannot get domains json', function () {
    $response = $this->get('/domains/json');

    $response->assertRedirect();
});

test('authenticated user can view domain show', function () {
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
});

test('user cannot view domains from other projects', function () {
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
});

test('authenticated user can create domain', function () {
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
});

test('user cannot create domain with dns provider from other project', function () {
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
});

test('authenticated user can delete domain', function () {
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
});

test('user cannot delete domains from other projects', function () {
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
});

test('authenticated user can get available domains from dns provider', function () {
    $this->actingAs($this->user);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $response = $this->get("/domains/{$dnsProvider->id}/available");

    $response->assertOk();
});

test('user cannot get available domains from dns provider in other project', function () {
    $this->actingAs($this->user);

    // Create a different project for the other user
    $otherProject = Project::factory()->create();
    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->otherUser->id,
        'project_id' => $otherProject->id,
    ]);

    $response = $this->get("/domains/{$otherDnsProvider->id}/available");

    $response->assertForbidden();
});

test('authenticated user can view dns records index', function () {
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
});

test('user cannot view dns records for domains from other projects', function () {
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
});

test('authenticated user can get dns records json', function () {
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
});

test('user cannot get dns records json for domains from other projects', function () {
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
});

test('authenticated user can create dns record', function () {
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
});

test('user cannot create dns record for domains from other projects', function () {
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
});

test('authenticated user can update dns record', function () {
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
});

test('user cannot update dns record from domains in other projects', function () {
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
});

test('user cannot update dns record that does not belong to domain', function () {
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
});

test('authenticated user can delete dns record', function () {
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
});

test('user cannot delete dns record from domains in other projects', function () {
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
});

test('user cannot delete dns record that does not belong to domain', function () {
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
});

test('domain not found returns 404', function () {
    $this->actingAs($this->user);

    $response = $this->get('/domains/999999');

    $response->assertNotFound();
});

test('dns record show route does not exist', function () {
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

    $response->assertStatus(405);
    // Method not allowed
});

test('dns provider not found returns 404', function () {
    $this->actingAs($this->user);

    $response = $this->get('/domains/999999/available');

    $response->assertNotFound();
});

test('dns records are ordered by type and name', function () {
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
    expect($records)->toHaveCount(4);

    // Should be ordered by type first (A before CNAME), then by name
    expect($records[0]['type'])->toEqual('A');
    expect($records[0]['name'])->toEqual('alpha');
    expect($records[1]['type'])->toEqual('A');
    expect($records[1]['name'])->toEqual('beta');
    expect($records[2]['type'])->toEqual('CNAME');
    expect($records[2]['name'])->toEqual('alpha');
    expect($records[3]['type'])->toEqual('CNAME');
    expect($records[3]['name'])->toEqual('zebra');
});

test('user cannot access domains from other projects', function () {
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
});

test('user cannot create domain in other project', function () {
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
});

test('user cannot delete domain from other project', function () {
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
});

test('authenticated user can sync dns records', function () {
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
});

test('sync dns records returns error when provider fails', function () {
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
        throw new RuntimeException('Domain is not opted in to API access.');
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
});

test('add domain fails when record sync fails', function () {
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

        throw new RuntimeException('Domain is not opted in to API access.');
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
});

test('user cannot sync dns records for domains from other projects', function () {
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
});

test('available domains returns cached value when cache exists', function () {
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
    expect($response->json())->toEqual($cachedDomains);
});

test('refresh domains skips cache and fetches from provider', function () {
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
    expect($data)->toHaveCount(2);
    expect($data[0]['name'])->toEqual('fresh.com');
    expect($data[1]['name'])->toEqual('new.com');

    // Cache should now be updated with fresh data
    $cached = Cache::get("dns_provider_{$dnsProvider->id}_domains");
    expect($cached)->toHaveCount(2);
    expect($cached[0]['name'])->toEqual('fresh.com');
});

test('refresh domains updates cache for subsequent available calls', function () {
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
    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['name'])->toEqual('example.com');
});
