<?php

namespace Tests\Feature\API;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Facades\SSH;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SSLTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_ssl_list(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites.ssls', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertSuccessful();
    }

    public function test_see_ssl(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Ssl $ssl */
        $ssl = Ssl::factory()->create([
            'site_id' => $site->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites.ssls.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
            'ssl' => $ssl,
        ]))
            ->assertSuccessful();
    }

    public function test_create_letsencrypt_ssl(): void
    {
        SSH::fake('Successfully received certificate');

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('POST', route('api.projects.servers.sites.ssls.create-letsencrypt', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]), [
            'email' => 'ssl@example.com',
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'type' => SslType::LETSENCRYPT,
                'status' => SslStatus::CREATING,
            ]);

        $this->assertDatabaseHas('ssls', [
            'site_id' => $site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATED,
            'email' => 'ssl@example.com',
        ]);
    }

    public function test_create_custom_ssl(): void
    {
        SSH::fake('Successfully received certificate');

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('POST', route('api.projects.servers.sites.ssls.create-custom', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]), [
            'certificate' => 'certificate',
            'private' => 'private',
            'expires_at' => now()->addYear()->format('Y-m-d'),
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'type' => SslType::CUSTOM,
                'status' => SslStatus::CREATING,
            ]);

        $this->assertDatabaseHas('ssls', [
            'site_id' => $site->id,
            'type' => SslType::CUSTOM,
            'status' => SslStatus::CREATED,
        ]);
    }

    public function test_activate_ssl(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Ssl $ssl */
        $ssl = Ssl::factory()->create([
            'site_id' => $site->id,
            'is_active' => false,
        ]);

        $this->json('POST', route('api.projects.servers.sites.ssls.activate', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
            'ssl' => $ssl,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'is_active' => true,
            ]);
    }

    public function test_deactivate_ssl(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Ssl $ssl */
        $ssl = Ssl::factory()->create([
            'site_id' => $site->id,
            'is_active' => true,
        ]);

        $this->json('POST', route('api.projects.servers.sites.ssls.deactivate', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
            'ssl' => $ssl,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'is_active' => false,
            ]);
    }

    public function test_delete_ssl(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Ssl $ssl */
        $ssl = Ssl::factory()->create([
            'site_id' => $site->id,
        ]);

        $this->json('DELETE', route('api.projects.servers.sites.ssls.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
            'ssl' => $ssl,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }
}
