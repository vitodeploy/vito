<?php

use App\Models\DNSProvider;
use App\Models\DNSRecord;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->ensureHasDefaultProject();

    $this->otherUser = User::factory()->create();
    $this->otherUser->ensureHasDefaultProject();
});

test('authenticated user can list dns records for domain', function () {
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

    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records");

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

test('user cannot list dns records for other users domain', function () {
    Sanctum::actingAs($this->user, ['read']);

    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->otherUser->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $otherDomain = Domain::factory()->create([
        'user_id' => $this->otherUser->id,
        'dns_provider_id' => $otherDnsProvider->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $response = $this->getJson("/api/projects/{$this->otherUser->current_project_id}/domains/{$otherDomain->id}/records");

    $response->assertForbidden();
});

test('authenticated user can create dns record', function () {
    Sanctum::actingAs($this->user, ['write']);

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

    $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records", $recordData);

    $response->assertCreated()
        ->assertJsonStructure([
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
});

test('user cannot create dns record for other users domain', function () {
    Sanctum::actingAs($this->user, ['write']);

    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->otherUser->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $otherDomain = Domain::factory()->create([
        'user_id' => $this->otherUser->id,
        'dns_provider_id' => $otherDnsProvider->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $recordData = [
        'type' => 'A',
        'name' => 'www',
        'content' => '192.168.1.1',
    ];

    $response = $this->postJson("/api/projects/{$this->otherUser->current_project_id}/domains/{$otherDomain->id}/records", $recordData);

    $response->assertForbidden();
});

test('user without write ability cannot create dns record', function () {
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

    $recordData = [
        'type' => 'A',
        'name' => 'www',
        'content' => '192.168.1.1',
    ];

    $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records", $recordData);

    $response->assertForbidden();
});

test('authenticated user can view dns record', function () {
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

    $record = DNSRecord::factory()->create([
        'domain_id' => $domain->id,
        'type' => 'A',
        'name' => 'www',
        'content' => '192.168.1.1',
    ]);

    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/{$record->id}");

    $response->assertOk()
        ->assertJsonStructure([
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
        ])
        ->assertJsonFragment([
            'id' => $record->id,
            'type' => 'A',
            'name' => 'www',
        ]);
});

test('user cannot view dns record from other users domain', function () {
    Sanctum::actingAs($this->user, ['read']);

    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->otherUser->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $otherDomain = Domain::factory()->create([
        'user_id' => $this->otherUser->id,
        'dns_provider_id' => $otherDnsProvider->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $otherRecord = DNSRecord::factory()->create([
        'domain_id' => $otherDomain->id,
    ]);

    $response = $this->getJson("/api/projects/{$this->otherUser->current_project_id}/domains/{$otherDomain->id}/records/{$otherRecord->id}");

    $response->assertForbidden();
});

test('user cannot view dns record that does not belong to domain', function () {
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

    $otherDomain = Domain::factory()->create([
        'user_id' => $this->user->id,
        'dns_provider_id' => $dnsProvider->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $record = DNSRecord::factory()->create([
        'domain_id' => $otherDomain->id,
    ]);

    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/{$record->id}");

    $response->assertNotFound();
});

test('authenticated user can update dns record', function () {
    Sanctum::actingAs($this->user, ['write']);

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

    $response = $this->patchJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/{$record->id}", $updateData);

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
});

test('user cannot update dns record from other users domain', function () {
    Sanctum::actingAs($this->user, ['write']);

    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->otherUser->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $otherDomain = Domain::factory()->create([
        'user_id' => $this->otherUser->id,
        'dns_provider_id' => $otherDnsProvider->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $otherRecord = DNSRecord::factory()->create([
        'domain_id' => $otherDomain->id,
    ]);

    $updateData = ['content' => '192.168.1.2'];

    $response = $this->patchJson("/api/projects/{$this->otherUser->current_project_id}/domains/{$otherDomain->id}/records/{$otherRecord->id}", $updateData);

    $response->assertForbidden();
});

test('user cannot update dns record that does not belong to domain', function () {
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

    $otherDomain = Domain::factory()->create([
        'user_id' => $this->user->id,
        'dns_provider_id' => $dnsProvider->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $record = DNSRecord::factory()->create([
        'domain_id' => $otherDomain->id,
    ]);

    $updateData = ['content' => '192.168.1.2'];

    $response = $this->patchJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/{$record->id}", $updateData);

    $response->assertNotFound();
});

test('authenticated user can delete dns record', function () {
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

    $record = DNSRecord::factory()->create([
        'domain_id' => $domain->id,
    ]);

    $response = $this->deleteJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/{$record->id}");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'DNS record deleted successfully']);

    $this->assertDatabaseMissing('dns_records', ['id' => $record->id]);
});

test('user cannot delete dns record from other users domain', function () {
    Sanctum::actingAs($this->user, ['write']);

    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->otherUser->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $otherDomain = Domain::factory()->create([
        'user_id' => $this->otherUser->id,
        'dns_provider_id' => $otherDnsProvider->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $otherRecord = DNSRecord::factory()->create([
        'domain_id' => $otherDomain->id,
    ]);

    $response = $this->deleteJson("/api/projects/{$this->otherUser->current_project_id}/domains/{$otherDomain->id}/records/{$otherRecord->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('dns_records', ['id' => $otherRecord->id]);
});

test('user cannot delete dns record that does not belong to domain', function () {
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

    $otherDomain = Domain::factory()->create([
        'user_id' => $this->user->id,
        'dns_provider_id' => $dnsProvider->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $record = DNSRecord::factory()->create([
        'domain_id' => $otherDomain->id,
    ]);

    $response = $this->deleteJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/{$record->id}");

    $response->assertNotFound();

    $this->assertDatabaseHas('dns_records', ['id' => $record->id]);
});

test('user without write ability cannot delete dns record', function () {
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

    $record = DNSRecord::factory()->create([
        'domain_id' => $domain->id,
    ]);

    $response = $this->deleteJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/{$record->id}");

    $response->assertForbidden();
});

test('dns record not found returns 404', function () {
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

    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/999999");

    $response->assertNotFound();
});

test('dns records are ordered by type and name', function () {
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

    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records");

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

test('user cannot access dns records from other projects', function () {
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

    // Create DNS record for the other project domain
    $otherProjectRecord = DNSRecord::factory()->create([
        'domain_id' => $otherProjectDomain->id,
    ]);

    // Should not be able to access DNS records from other project
    $response = $this->getJson("/api/projects/{$otherProject->id}/domains/{$otherProjectDomain->id}/records");

    $response->assertForbidden();

    // Should not be able to access specific DNS record from other project
    $response = $this->getJson("/api/projects/{$otherProject->id}/domains/{$otherProjectDomain->id}/records/{$otherProjectRecord->id}");

    $response->assertForbidden();
});

test('authenticated user can sync dns records', function () {
    Sanctum::actingAs($this->user, ['write']);

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

    $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/sync");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'DNS records synced successfully']);

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
    Sanctum::actingAs($this->user, ['write']);

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

    // Mock the DNS provider API call to throw an exception
    Http::fake(function () {
        throw new RuntimeException('Domain is not opted in to API access.');
    });

    $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/sync");

    $response->assertStatus(422);
    $this->assertStringContainsString('Failed to sync DNS records', $response->json('message'));
});

test('user cannot sync dns records for other users domain', function () {
    Sanctum::actingAs($this->user, ['write']);

    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->otherUser->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $otherDomain = Domain::factory()->create([
        'user_id' => $this->otherUser->id,
        'dns_provider_id' => $otherDnsProvider->id,
        'project_id' => $this->otherUser->current_project_id,
    ]);

    $response = $this->postJson("/api/projects/{$this->otherUser->current_project_id}/domains/{$otherDomain->id}/records/sync");

    $response->assertForbidden();
});

test('user without write ability cannot sync dns records', function () {
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

    $response = $this->postJson("/api/projects/{$this->user->current_project_id}/domains/{$domain->id}/records/sync");

    $response->assertForbidden();
});
