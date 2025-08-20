<?php

namespace Tests\Feature\API;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Facades\SSH;
use App\Models\Site;
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
}
