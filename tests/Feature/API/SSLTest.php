<?php

namespace Tests\Feature\API;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\PrepareLoadBalancer;

class SSLTest extends TestCase
{
    use PrepareLoadBalancer;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepare();
    }

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
}
