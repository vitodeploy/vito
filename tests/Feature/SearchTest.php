<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_server(): void
    {
        $this->actingAs($this->user);

        $this->get(route('search', ['q' => $this->server->name]))
            ->assertOk()
            ->assertJson([
                'results' => [
                    [
                        'type' => 'server',
                        'url' => route('servers.show', ['server' => $this->site->server]),
                        'text' => $this->server->name,
                        'project' => $this->server->project->name,
                    ],
                ],
            ]);
    }

    public function test_search_site(): void
    {
        $this->actingAs($this->user);

        $this->get(route('search', ['q' => $this->site->domain]))
            ->assertOk()
            ->assertJson([
                'results' => [
                    [
                        'type' => 'site',
                        'url' => route('servers.sites.show', ['server' => $this->site->server, 'site' => $this->site]),
                        'text' => $this->site->domain,
                        'project' => $this->site->server->project->name,
                    ],
                ],
            ]);
    }

    public function test_search_has_no_results(): void
    {
        $this->actingAs($this->user);

        $this->get(route('search', ['q' => 'nothing-will-found']))
            ->assertOk()
            ->assertJson([
                'results' => [],
            ]);
    }
}
