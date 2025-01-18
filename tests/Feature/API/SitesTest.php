<?php

namespace Tests\Feature\API;

use App\Enums\SourceControl;
use App\Facades\SSH;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SitesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider create_data
     */
    public function test_create_site(array $inputs): void
    {
        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], 201),
        ]);

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var \App\Models\SourceControl $sourceControl */
        $sourceControl = \App\Models\SourceControl::factory()->create([
            'provider' => SourceControl::GITHUB,
        ]);

        $inputs['source_control'] = $sourceControl->id;

        $this->json('POST', route('api.projects.servers.sites.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), $inputs)
            ->assertSuccessful()
            ->assertJsonFragment([
                'domain' => $inputs['domain'],
                'aliases' => $inputs['aliases'] ?? [],
                'user' => $inputs['user'] ?? $this->server->getSshUser(),
                'path' => '/home/'.($inputs['user'] ?? $this->server->getSshUser()).'/'.$inputs['domain'],
            ]);
    }

    public function test_see_sites_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'domain' => $site->domain,
            ]);
    }

    public function test_delete_site(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('DELETE', route('api.projects.servers.sites.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }

    public static function create_data(): array
    {
        return \Tests\Feature\SitesTest::create_data();
    }
}
