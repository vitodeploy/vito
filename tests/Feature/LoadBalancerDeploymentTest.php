<?php

use App\Actions\EnvironmentDeployment\AdvanceEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Actions\EnvironmentDeployment\FinishEnvironmentDeployment;
use App\Actions\EnvironmentDeployment\RollbackEnvironment;
use App\Actions\EnvironmentDeployment\RunComponentStage;
use App\Actions\EnvironmentDeployment\StartEnvironmentDeployment;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\DeploymentStage;
use App\Enums\EnvironmentDeploymentStatus;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\SourceControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake('');
    Http::preventStrayRequests();
    Http::fake(['https://api.github.com/repos/*/commits/*' => Http::response(['sha' => str_repeat('a', 40), 'commit' => ['message' => 'Pinned']])]);
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $source = SourceControl::factory()->github()->create(['project_id' => $this->server->project_id, 'user_id' => $this->user->id]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'custom', 'source_control_id' => $source->id]);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    $this->lb = Component::factory()->for($this->environment)->for($this->server)->create(['type' => ComponentType::LOAD_BALANCER, 'status' => ComponentStatus::READY, 'path' => '']);
    $this->environment->primary_component_id = $this->component->id;
    $this->environment->save();
});

function advanceLoadBalancerRun(EnvironmentDeployment $run, ?int $failure = null): void
{
    $run->refresh();
    $stage = $run->current_stage;
    $recovering = $run->is_recovering;
    foreach (app(AdvanceEnvironmentDeployment::class)->participants($run, $stage)->get() as $deployment) {
        if ($failure === $deployment->component_id) {
            app(RunComponentStage::class)->failed($deployment->id, $stage, $recovering);
        } else {
            app(RunComponentStage::class)->run($deployment->id, $stage, $recovering);
        }
        app(AdvanceEnvironmentDeployment::class)->advance($run->id, $stage, $recovering);
    }
    $run->refresh();
}

test('load balancers participate only in release without app scripts releases or pruning', function () {
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    $run->refresh();
    expect($run->deployments)->toHaveCount(2);
    $lbDeployment = $run->deployments()->where('component_id', $this->lb->id)->sole();
    expect($lbDeployment->release)->toBeNull()->and($lbDeployment->active)->toBeFalse();
    foreach (DeploymentStage::cases() as $stage) {
        $ids = app(AdvanceEnvironmentDeployment::class)->participants($run, $stage)->pluck('component_id');
        expect($ids->contains($this->lb->id))->toBe($stage === DeploymentStage::RELEASE);
    }
    for ($i = 0; $i < 3; $i++) {
        advanceLoadBalancerRun($run);
    }
    SSH::fake('');
    app(RunComponentStage::class)->run($lbDeployment->id, DeploymentStage::RELEASE);
    SSH::assertExecutedContains('component-'.$this->lb->id);
    SSH::assertNotExecutedContains('git clone');
    SSH::assertNotExecutedContains('/current');
    SSH::assertNotExecutedContains('.env');
    advanceLoadBalancerRun($run);
    advanceLoadBalancerRun($run);
    expect($run->status)->toBe(EnvironmentDeploymentStatus::FINISHED);
    expect($lbDeployment->fresh()->active)->toBeFalse();
    SSH::fake('');
    app(FinishEnvironmentDeployment::class)->finish($run->id);
    SSH::assertNotExecutedContains("'/releases'");
    $this->actingAs($this->user)->get('/applications/'.$this->application->id.'/environments/'.$this->environment->id.'/deployments/'.$run->id)
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('deployment.deployments.1.component_type', 'load_balancer'));
});

test('load balancer release failure waits for app settlement then joins recovery', function (bool $recoveryFails) {
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    for ($i = 0; $i < 3; $i++) {
        advanceLoadBalancerRun($run);
    }
    $deployment = $run->deployments()->where('component_id', $this->lb->id)->sole();
    app(RunComponentStage::class)->failed($deployment->id, DeploymentStage::RELEASE);
    app(AdvanceEnvironmentDeployment::class)->advance($run->id, DeploymentStage::RELEASE);
    expect($run->fresh()->is_recovering)->toBeFalse();
    advanceLoadBalancerRun($run);
    expect($run->is_recovering)->toBeTrue();
    advanceLoadBalancerRun($run, $recoveryFails ? $this->lb->id : null);
    expect($run->status)->toBe($recoveryFails ? EnvironmentDeploymentStatus::PARTIAL : EnvironmentDeploymentStatus::ROLLED_BACK);
    expect($this->environment->fresh()->live_run_id)->toBeNull();
    $this->assertDatabaseHas('components', ['id' => $this->lb->id, 'needs_redeploy' => false]);
})->with([false, true]);

test('manual rollback reloads new load balancers but live joins never include them', function () {
    $run = app(DeployEnvironment::class)->run($this->environment);
    app(StartEnvironmentDeployment::class)->start($run->id);
    $run->refresh();
    for ($i = 0; $i < 5; $i++) {
        advanceLoadBalancerRun($run);
    }
    $new = Component::factory()->for($this->environment)->create(['type' => ComponentType::LOAD_BALANCER, 'status' => ComponentStatus::READY]);
    $new->server->services()->create(['name' => 'nginx', 'type' => 'webserver', 'version' => 'latest', 'status' => ServiceStatus::READY]);
    expect(app(DeployEnvironment::class)->join($this->environment))->toBeNull();
    $rollback = app(RollbackEnvironment::class)->run($this->environment->fresh(), $run);
    app(StartEnvironmentDeployment::class)->start($rollback->id);
    $rollback->refresh();
    expect($rollback->deployments->pluck('component_id')->all())->toContain($this->lb->id, $new->id);
    expect($rollback->current_stage)->toBe(DeploymentStage::RELEASE);
    advanceLoadBalancerRun($rollback);
    expect($rollback->current_stage)->toBe(DeploymentStage::POST_RELEASE);
    advanceLoadBalancerRun($rollback);
    expect($rollback->status)->toBe(EnvironmentDeploymentStatus::FINISHED);
    $app = Component::factory()->for($this->environment)->create(['status' => ComponentStatus::READY]);
    $join = app(DeployEnvironment::class)->join($this->environment->fresh());
    app(StartEnvironmentDeployment::class)->start($join->id);
    expect($join->fresh()->deployments->pluck('component_id')->all())->toBe([$app->id]);
});
