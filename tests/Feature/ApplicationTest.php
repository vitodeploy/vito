<?php

namespace Tests\Feature;

use App\Jobs\Site\UpdateBranch;
use App\Models\GitHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_application()
    {
        $this->actingAs($this->user);

        $this->get(
            route('servers.sites.show', [
                'server' => $this->server,
                'site' => $this->site,
            ])
        )
            ->assertOk()
            ->assertSee($this->site->domain);
    }

    public function test_update_deployment_script()
    {
        $this->actingAs($this->user);

        $this->post(
            route('servers.sites.application.deployment-script', [
                'server' => $this->server,
                'site' => $this->site,
            ]),
            [
                'script' => 'some script',
            ]
        )->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('deployment_scripts', [
            'site_id' => $this->site->id,
            'content' => 'some script',
        ]);
    }

    public function test_change_branch()
    {
        Bus::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.sites.application.branch', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'branch' => 'master',
        ])->assertSessionDoesntHaveErrors();

        Bus::assertDispatched(UpdateBranch::class);
    }

    public function test_enable_auto_deployment()
    {
        Http::fake([
            'github.com/*' => Http::response([
                'id' => '123',
            ], 201),
        ]);

        $this->actingAs($this->user);

        $this->post(route('servers.sites.application.auto-deployment', [
            'server' => $this->server,
            'site' => $this->site,
        ]))->assertSessionDoesntHaveErrors();

        $this->site->refresh();

        $this->assertTrue($this->site->auto_deployment);
    }

    public function test_disable_auto_deployment()
    {
        Http::fake([
            'github.com/*' => Http::response([], 204),
        ]);

        $this->actingAs($this->user);

        GitHook::factory()->create([
            'site_id' => $this->site->id,
            'source_control_id' => $this->site->source_control_id,
        ]);

        $this->delete(route('servers.sites.application.auto-deployment', [
            'server' => $this->server,
            'site' => $this->site,
        ]))->assertSessionDoesntHaveErrors();

        $this->site->refresh();

        $this->assertFalse($this->site->auto_deployment);
    }
}
