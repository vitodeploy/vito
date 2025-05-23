<?php

namespace Tests\Feature\API;

use App\Enums\RedirectStatus;
use App\Facades\SSH;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RedirectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_redirect(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.sites.redirects.create', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'from' => 'testing/path',
            'to' => 'https://example.com',
            'mode' => 301,
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'from' => 'testing/path',
                'to' => 'https://example.com',
                'mode' => 301,
                'status' => RedirectStatus::READY,
            ]);
    }

    public function test_see_redirects_list(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        /** @var Redirect $redirect */
        $redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites.redirects.index', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'from' => $redirect->from,
                'to' => $redirect->to,
                'mode' => $redirect->mode,
                'status' => $redirect->status,
            ]);
    }

    public function test_delete_redirect(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['write']);

        $this->json('DELETE', route('api.projects.servers.sites.redirects.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $this->site,
            'redirect' => $this->redirect->id,
        ]))
            ->assertSuccessful()
            ->assertNoContent();

        $this->assertDatabaseMissing('redirects', [
            'id' => $this->redirect->id,
        ]);
    }
}
