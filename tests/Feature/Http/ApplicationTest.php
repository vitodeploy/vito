<?php

namespace Tests\Feature\Http;

use App\Http\Livewire\Application\ChangeBranch;
use App\Http\Livewire\Application\Deploy;
use App\Http\Livewire\Application\DeploymentScript;
use App\Http\Livewire\Application\LaravelApp;
use App\Jobs\Site\UpdateBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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
                'site' => $this->site
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
            'content' => 'some script'
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
}
