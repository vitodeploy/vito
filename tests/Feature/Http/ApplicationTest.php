<?php

namespace Tests\Feature\Http;

use App\Http\Livewire\Application\AutoDeployment;
use App\Http\Livewire\Application\ChangeBranch;
use App\Http\Livewire\Application\Deploy;
use App\Http\Livewire\Application\DeploymentScript;
use App\Http\Livewire\Application\LaravelApp;
use App\Jobs\Site\UpdateBranch;
use App\Models\GitHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
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
            ->assertSeeLivewire(LaravelApp::class);
    }

    public function test_update_deployment_script()
    {
        $this->actingAs($this->user);

        Livewire::test(Deploy::class, ['site' => $this->site])
            ->assertDontSeeText('Deploy');

        Livewire::test(DeploymentScript::class, ['site' => $this->site])
            ->set('script', 'some script')
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('deployment_scripts', [
            'site_id' => $this->site->id,
            'content' => 'some script',
        ]);

        $this->site->refresh();

        Livewire::test(Deploy::class, ['site' => $this->site])
            ->assertSeeText('Deploy');
    }

    public function test_change_branch()
    {
        Bus::fake();

        $this->actingAs($this->user);

        Livewire::test(ChangeBranch::class, ['site' => $this->site])
            ->set('branch', 'master')
            ->call('change')
            ->assertSuccessful();

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

        Livewire::test(AutoDeployment::class, ['site' => $this->site])
            ->call('enable')
            ->assertSuccessful();

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

        Livewire::test(AutoDeployment::class, ['site' => $this->site])
            ->call('disable')
            ->assertSuccessful();

        $this->site->refresh();

        $this->assertFalse($this->site->auto_deployment);
    }
}
