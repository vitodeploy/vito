<?php

use App\Models\DNSProvider;
use App\Models\Domain;
use App\Models\Project;
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

test('authenticated user can list domains', function () {
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
});

test('unauthenticated user cannot list domains', function () {
    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains");

    $response->assertUnauthorized();
});

test('user without read ability cannot list domains', function () {
    Sanctum::actingAs($this->user, ['write']);

    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains");

    $response->assertForbidden();
});

test('user can see all domains in their project', function () {
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
});

test('user can access domains created by other users in same project', function () {
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
});

test('authenticated user can create domain', function () {
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
});

test('create domain fails when record sync fails', function () {
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

        throw new RuntimeException('Domain is not opted in to API access.');
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
});

test('user cannot create domain with dns provider from other project', function () {
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
});

test('user without write ability cannot create domain', function () {
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
});

test('authenticated user can view domain', function () {
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
});

test('user cannot view domains from other projects', function () {
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
});

test('authenticated user can delete domain', function () {
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
});

test('user cannot delete domains from other projects', function () {
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
});

test('user without write ability cannot delete domain', function () {
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
});

test('authenticated user can get available domains from dns provider', function () {
    Sanctum::actingAs($this->user, ['read']);

    $dnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/{$dnsProvider->id}/available");

    $response->assertNotFound();
});

test('user cannot get available domains from dns provider in other project', function () {
    Sanctum::actingAs($this->user, ['read']);

    // Create a different project for the other user
    $otherProject = Project::factory()->create();
    $otherDnsProvider = DNSProvider::factory()->create([
        'user_id' => $this->otherUser->id,
        'project_id' => $otherProject->id,
    ]);

    $response = $this->getJson("/api/projects/{$otherProject->id}/domains/{$otherDnsProvider->id}/available");

    $response->assertNotFound();
});

test('domain not found returns 404', function () {
    Sanctum::actingAs($this->user, ['read']);

    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/999999");

    $response->assertNotFound();
});

test('dns provider not found returns 404', function () {
    Sanctum::actingAs($this->user, ['read']);

    $response = $this->getJson("/api/projects/{$this->user->current_project_id}/domains/999999/available");

    $response->assertNotFound();
});

test('domain pagination works correctly', function () {
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
    expect($response->json('data'))->toHaveCount(25);
});

test('user cannot access domains from other projects', function () {
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
    $response->assertJsonFragment(['id' => $currentProjectDomain->id]);

    // Only domains from current project
    // Should not be able to access domain from other project
    $response = $this->getJson("/api/projects/{$otherProject->id}/domains/{$otherProjectDomain->id}");

    $response->assertForbidden();
});

test('user cannot create domain in other project', function () {
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
});

test('user cannot delete domain from other project', function () {
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
});
