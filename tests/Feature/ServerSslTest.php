<?php

namespace Tests\Feature;

use App\Enums\SslStatus;
use App\Facades\SSH;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ServerSslTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_server_ssls_list(): void
    {
        $this->actingAs($this->user);

        Ssl::factory()->serverLevel()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('server-ssls', ['server' => $this->server->id]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('server-ssls/index')
                ->has('ssls.data', 1)
                ->has('domains')
            );
    }

    public function test_create_csr(): void
    {
        SSH::fake('CSR GENERATED SUCCESSFULLY');

        $this->actingAs($this->user);

        $this->post(route('server-ssls.store', ['server' => $this->server->id]), [
            'type' => 'csr',
            'common_name' => 'example.com',
            'organization' => 'Test Org',
            'organizational_unit' => 'IT',
            'city' => 'San Francisco',
            'state' => 'California',
            'country' => 'US',
            'email' => 'admin@example.com',
            'key_size' => '2048',
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('ssls', [
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => 'csr',
            'status' => SslStatus::CREATED,
            'has_csr' => true,
            'domains' => $this->castAsJson(['example.com']),
        ]);
    }

    public function test_create_csr_validation(): void
    {
        $this->actingAs($this->user);

        $this->post(route('server-ssls.store', ['server' => $this->server->id]), [
            'type' => 'csr',
        ])
            ->assertSessionHasErrors(['common_name', 'organization', 'city', 'state', 'country']);
    }

    public function test_create_csr_invalid_type(): void
    {
        $this->actingAs($this->user);

        $this->post(route('server-ssls.store', ['server' => $this->server->id]), [
            'type' => 'invalid',
        ])
            ->assertSessionHasErrors('type');
    }

    public function test_create_custom_ssl(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $cert = $this->generateSelfSignedCert();

        $this->post(route('server-ssls.store', ['server' => $this->server->id]), [
            'type' => 'custom',
            'certificate' => $cert['certificate'],
            'private_key' => $cert['private_key'],
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('ssls', [
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => 'custom',
            'status' => SslStatus::CREATED,
        ]);
    }

    public function test_create_custom_ssl_validation(): void
    {
        $this->actingAs($this->user);

        $this->post(route('server-ssls.store', ['server' => $this->server->id]), [
            'type' => 'custom',
        ])
            ->assertSessionHasErrors(['certificate', 'private_key']);
    }

    public function test_activate_csr(): void
    {
        SSH::fake('SSL ACTIVATED SUCCESSFULLY');

        $this->actingAs($this->user);

        $cert = $this->generateSelfSignedCert();

        $ssl = Ssl::factory()->serverLevel()->create([
            'server_id' => $this->server->id,
            'status' => SslStatus::CREATED,
            'has_csr' => true,
            'csr_data' => [
                'common_name' => 'example.com',
                'organization' => 'Test Org',
                'city' => 'San Francisco',
                'state' => 'California',
                'country' => 'US',
                'key_size' => 2048,
                'pk_path' => '/etc/ssl/vito/'.$this->server->id.'/private.key',
            ],
        ]);

        $this->post(route('server-ssls.activate', ['server' => $this->server->id, 'ssl' => $ssl->id]), [
            'certificate' => $cert['certificate'],
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('ssls', [
            'id' => $ssl->id,
            'type' => 'custom',
            'status' => SslStatus::CREATED,
        ]);
    }

    public function test_delete_server_ssl(): void
    {
        SSH::fake('SSL DELETED SUCCESSFULLY');

        $this->actingAs($this->user);

        $ssl = Ssl::factory()->serverLevel()->create([
            'server_id' => $this->server->id,
            'status' => SslStatus::CREATED,
        ]);

        $this->delete(route('server-ssls.destroy', ['server' => $this->server->id, 'ssl' => $ssl->id]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('ssls', [
            'id' => $ssl->id,
        ]);
    }

    public function test_delete_wildcard_ssl_runs_certbot_delete(): void
    {
        SSH::fake('SSL DELETED SUCCESSFULLY');

        $this->actingAs($this->user);

        $ssl = Ssl::factory()->serverLevel()->create([
            'server_id' => $this->server->id,
            'type' => 'letsencrypt',
            'status' => SslStatus::CREATED,
            'is_wildcard' => true,
        ]);

        $this->delete(route('server-ssls.destroy', ['server' => $this->server->id, 'ssl' => $ssl->id]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('ssls', [
            'id' => $ssl->id,
        ]);

        SSH::assertExecutedContains('certbot delete');
    }

    public function test_download_csr_not_found_without_csr_path(): void
    {
        $this->actingAs($this->user);

        $ssl = Ssl::factory()->serverLevel()->create([
            'server_id' => $this->server->id,
            'status' => SslStatus::CREATED,
            'has_csr' => true,
            'csr_data' => [
                'common_name' => 'example.com',
            ],
        ]);

        $this->get(route('server-ssls.download', ['server' => $this->server->id, 'ssl' => $ssl->id]))
            ->assertNotFound();
    }

    public function test_download_csr_not_available_without_has_csr(): void
    {
        $this->actingAs($this->user);

        $ssl = Ssl::factory()->serverLevel()->create([
            'server_id' => $this->server->id,
            'status' => SslStatus::CREATED,
            'has_csr' => false,
        ]);

        $this->get(route('server-ssls.download', ['server' => $this->server->id, 'ssl' => $ssl->id]))
            ->assertNotFound();
    }

    public function test_cannot_access_other_servers_ssl(): void
    {
        $this->actingAs($this->user);

        $ssl = Ssl::factory()->serverLevel()->create([
            'status' => SslStatus::CREATED,
        ]);

        $this->delete(route('server-ssls.destroy', ['server' => $this->server->id, 'ssl' => $ssl->id]))
            ->assertForbidden();
    }

    /**
     * Generate a self-signed certificate for testing.
     *
     * @return array{certificate: string, private_key: string}
     */
    private function generateSelfSignedCert(): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new([
            'commonName' => 'example.com',
            'organizationName' => 'Test',
            'countryName' => 'US',
            'stateOrProvinceName' => 'CA',
            'localityName' => 'SF',
        ], $key);
        $cert = openssl_csr_sign($csr, null, $key, 365);

        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($key, $keyPem);

        return [
            'certificate' => $certPem,
            'private_key' => $keyPem,
        ];
    }
}
