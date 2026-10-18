<?php

use App\Contracts\VitoEnum;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentStatus;
use App\Facades\SSH;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\CronJob;
use App\Models\Deployment;
use App\Models\DeploymentScript;
use App\Models\Environment;
use App\Models\IsolatedUser;
use App\Models\Server;
use App\Models\Worker;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('application models expose their relationships and typed state', function () {
    $application = Application::factory()->create([
        'project_id' => $this->server->project_id,
        'source_control_id' => $this->site->source_control_id,
    ]);
    $environment = Environment::factory()->for($application)->create();
    $isolatedUser = IsolatedUser::factory()->create(['server_id' => $this->server->id]);
    $component = Component::factory()->for($environment)->create([
        'server_id' => $this->server->id,
        'isolated_user_id' => $isolatedUser->id,
        'user' => $isolatedUser->username,
        'needs_redeploy' => 1,
    ]);
    $capability = ComponentCapability::factory()->for($component)->create([
        'config' => ['port' => 3000, 'enabled' => true],
    ]);
    $environment->forceFill(['primary_component_id' => $component->id])->save();
    $environmentScript = DeploymentScript::factory()->create(['site_id' => null, 'environment_id' => $environment->id]);
    $componentScript = DeploymentScript::factory()->create(['site_id' => null, 'component_id' => $component->id]);
    $worker = Worker::factory()->create(['server_id' => $this->server->id, 'component_id' => $component->id]);
    $cron = CronJob::factory()->create(['server_id' => $this->server->id, 'component_id' => $component->id]);

    $component->refresh()->load('environment.application.project', 'capabilities');
    expect($this->server->project->applications->sole()->is($application))->toBeTrue()
        ->and($application->sourceControl->is($this->site->sourceControl))->toBeTrue()
        ->and($application->environments->sole()->is($environment))->toBeTrue()
        ->and($environment->components->sole()->is($component))->toBeTrue()
        ->and($environment->primaryComponent->is($component))->toBeTrue()
        ->and($environment->status)->toBe(EnvironmentStatus::READY)
        ->and($environment->deploymentScripts->sole()->is($environmentScript))->toBeTrue()
        ->and($component->server->is($this->server))->toBeTrue()
        ->and($component->isolatedUser->is($isolatedUser))->toBeTrue()
        ->and($component->type)->toBe(ComponentType::APP)
        ->and($component->status)->toBe(ComponentStatus::PENDING)
        ->and($component->needs_redeploy)->toBeTrue()
        ->and($component->project()->is($application->project))->toBeTrue()
        ->and($component->deploymentScripts->sole()->is($componentScript))->toBeTrue()
        ->and($component->workers->sole()->is($worker))->toBeTrue()
        ->and($component->cronJobs->sole()->is($cron))->toBeTrue()
        ->and($this->server->components->sole()->is($component))->toBeTrue()
        ->and($isolatedUser->components->sole()->is($component))->toBeTrue()
        ->and($capability->component->is($component))->toBeTrue()
        ->and($capability->refresh()->config)->toBe(['port' => 3000, 'enabled' => true])
        ->and($capability->status)->toBe(ComponentStatus::PENDING)
        ->and($component->hasCapability('web'))->toBeTrue()
        ->and($component->hasCapability('worker'))->toBeFalse();
});

test('component paths stay stored when parent names and users change', function () {
    $component = Component::factory()->create();
    $environment = $component->environment;
    $path = '/home/'.$component->server->ssh_user.'/app-'.$environment->application_id.'-'.$environment->slug;

    expect($component->user)->toBe($component->server->ssh_user)
        ->and($component->server->project_id)->toBe($component->project()->id)
        ->and($component->basePath())->toBe($path);
    $component->update(['user' => 'different-user']);
    $environment->update(['name' => 'Renamed', 'slug' => 'renamed']);
    $environment->application->update(['name' => 'Renamed application']);
    $component->refresh();

    expect($component->basePath())->toBe($path)
        ->and($component->currentPath())->toBe($path.'/current')
        ->and($component->releasesPath())->toBe($path.'/releases')
        ->and($component->sharedPath())->toBe($path.'/shared')
        ->and($component->envPath())->toBe($path.'/shared/.env');
    $this->assertDatabaseHas('components', ['id' => $component->id, 'path' => $path]);
});

test('component deployments do not invoke site release pruning', function () {
    SSH::fake();
    $component = Component::factory()->create(['server_id' => $this->server->id]);
    $this->site->jsonUpdate('type_data', 'modern_deployment_history', 0);
    $deployment = Deployment::factory()->create([
        'site_id' => null,
        'component_id' => $component->id,
        'deployment_script_id' => null,
        'log_id' => null,
        'release' => 'release-one',
    ]);

    expect($component->deployments->sole()->is($deployment))->toBeTrue()
        ->and($deployment->component->is($component))->toBeTrue()
        ->and($deployment->site)->toBeNull()
        ->and($deployment->deploymentScript)->toBeNull()
        ->and(SSH::getExecutedCommands())->toBe([]);
    $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'release' => 'release-one']);
});

test('application schema enforces scoped unique keys', function (string $key) {
    $component = Component::factory()->create();
    $environment = $component->environment;
    $application = $environment->application;
    $capability = ComponentCapability::factory()->for($component)->create();

    $duplicate = match ($key) {
        'application name' => $application->replicate(),
        'environment name' => $environment->replicate()->fill(['slug' => 'different']),
        'environment slug' => $environment->replicate()->fill(['name' => 'Different']),
        'component name' => $component->replicate()->fill(['user' => 'different']),
        'component placement' => $component->replicate()->fill(['name' => 'different']),
        'capability' => $capability->replicate(),
    };

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);
})->with(['application name', 'environment name', 'environment slug', 'component name', 'component placement', 'capability']);

test('application unique keys permit reuse outside their parent scope', function () {
    $component = Component::factory()->create();
    $application = $component->environment->application;
    $otherApplication = $application->replicate()->fill(['project_id' => $this->server->project_id]);
    $otherApplication->save();
    $environment = $component->environment->replicate()->fill(['application_id' => $otherApplication->id]);
    $environment->save();
    $otherComponent = $component->replicate()->fill(['environment_id' => $environment->id]);
    $otherComponent->save();

    ComponentCapability::factory()->for($component)->create();
    $capability = ComponentCapability::factory()->for($otherComponent)->create();

    $this->assertDatabaseHas('component_capabilities', ['id' => $capability->id, 'component_id' => $otherComponent->id]);
});

test('application nullable foreign keys clear when their targets are deleted', function () {
    $application = Application::factory()->create(['source_control_id' => $this->site->source_control_id]);
    $environment = Environment::factory()->for($application)->create();
    $isolatedUser = IsolatedUser::factory()->create(['server_id' => $this->server->id]);
    $component = Component::factory()->for($environment)->create([
        'server_id' => $this->server->id,
        'isolated_user_id' => $isolatedUser->id,
    ]);
    $environment->forceFill(['primary_component_id' => $component->id])->save();

    $application->sourceControl->forceDelete();
    $isolatedUser->delete();
    expect($application->refresh()->source_control_id)->toBeNull()
        ->and($application->sourceControl)->toBeNull()
        ->and($component->refresh()->isolated_user_id)->toBeNull()
        ->and($component->isolatedUser)->toBeNull();

    $component->delete();
    expect($environment->refresh()->primary_component_id)->toBeNull()
        ->and($environment->primaryComponent)->toBeNull();
});

test('components restrict raw server deletion', function () {
    $component = Component::factory()->create();

    expect(fn () => Server::query()->whereKey($component->server_id)->delete())->toThrow(QueryException::class);
});

test('application ownership cascades without invoking remote model events', function (string $parent) {
    SSH::fake();
    $component = Component::factory()->create();
    $environment = $component->environment;
    $environment->forceFill(['primary_component_id' => $component->id])->save();
    $capability = ComponentCapability::factory()->for($component)->create();
    $worker = Worker::factory()->create(['server_id' => $component->server_id, 'component_id' => $component->id]);
    $cron = CronJob::factory()->create(['server_id' => $component->server_id, 'component_id' => $component->id]);
    $deployment = Deployment::factory()->create(['site_id' => null, 'component_id' => $component->id, 'deployment_script_id' => null]);
    $script = DeploymentScript::factory()->create(['site_id' => null, 'component_id' => $component->id]);
    $environmentScript = DeploymentScript::factory()->create(['site_id' => null, 'environment_id' => $environment->id]);
    $owner = match ($parent) {
        'component' => $component,
        'environment' => $environment,
        'application' => $environment->application,
        'project' => $environment->application->project,
    };

    $owner->newQuery()->whereKey($owner->id)->delete();

    foreach ([$component, $capability, $worker, $cron, $deployment, $script] as $deleted) {
        $this->assertDatabaseMissing($deleted->getTable(), ['id' => $deleted->id]);
    }
    if ($parent !== 'component') {
        $this->assertDatabaseMissing('environments', ['id' => $environment->id]);
        $this->assertDatabaseMissing('deployment_scripts', ['id' => $environmentScript->id]);
    }
    expect(SSH::getExecutedCommands())->toBe([]);
})->with(['component', 'environment', 'application', 'project']);

test('application migration round trips while preserving site data', function () {
    SSH::fake();
    $siteDeployment = Deployment::factory()->create(['site_id' => $this->site->id]);
    $siteScript = $this->site->deploymentScript;
    $siteWorker = Worker::factory()->create(['server_id' => $this->server->id, 'site_id' => $this->site->id]);
    $siteCron = CronJob::factory()->create(['server_id' => $this->server->id, 'site_id' => $this->site->id]);
    $component = Component::factory()->create();
    $component->environment->forceFill(['primary_component_id' => $component->id])->save();
    ComponentCapability::factory()->for($component)->create();
    Deployment::factory()->create(['site_id' => null, 'component_id' => $component->id, 'deployment_script_id' => null]);
    DeploymentScript::factory()->create(['site_id' => null, 'environment_id' => $component->environment_id]);
    DeploymentScript::factory()->create(['site_id' => null, 'component_id' => $component->id]);
    Worker::factory()->create(['server_id' => $component->server_id, 'component_id' => $component->id]);
    CronJob::factory()->create(['server_id' => $component->server_id, 'component_id' => $component->id]);
    $migration = require database_path('migrations/2026_10_17_171647_create_applications_tables.php');
    $deploymentsMigration = require database_path('migrations/2026_10_18_160459_create_environment_deployments_table.php');

    $deploymentsMigration->down();
    expect(Schema::hasTable('environment_deployments'))->toBeFalse();
    $migration->down();
    expect(Schema::hasTable('applications'))->toBeFalse()
        ->and(Schema::hasTable('environments'))->toBeFalse()
        ->and(Schema::hasTable('components'))->toBeFalse()
        ->and(Schema::hasTable('component_capabilities'))->toBeFalse();
    foreach (['deployments', 'deployment_scripts', 'workers', 'cron_jobs'] as $table) {
        expect(Schema::hasColumn($table, 'component_id'))->toBeFalse();
    }
    foreach (['deployments', 'deployment_scripts'] as $table) {
        $column = collect(Schema::getColumns($table))->firstWhere('name', 'site_id');
        expect($column['nullable'])->toBeFalse();
    }
    $migration->up();
    $deploymentsMigration->up();
    expect(Schema::hasColumn('environments', 'live_run_id'))->toBeTrue()
        ->and(Schema::hasColumn('deployments', 'previous_deployment_id'))->toBeTrue();

    foreach ([$siteDeployment, $siteScript, $siteWorker, $siteCron] as $preserved) {
        $this->assertDatabaseHas($preserved->getTable(), ['id' => $preserved->id, 'site_id' => $this->site->id]);
    }
    expect(Component::query()->count())->toBe(0)
        ->and(SSH::getExecutedCommands())->toBe([]);
});

test('application lifecycle state and generated paths cannot be mass assigned', function () {
    $component = Component::factory()->create();
    $path = $component->path;
    $component->fill(['path' => '/different', 'status' => ComponentStatus::READY]);
    expect($component->path)->toBe($path)->and($component->status)->toBe(ComponentStatus::PENDING);

    $environment = $component->environment;
    $environment->fill(['status' => EnvironmentStatus::DELETING]);
    expect($environment->status)->toBe(EnvironmentStatus::READY);

    $capability = ComponentCapability::factory()->for($component)->create();
    $capability->fill(['status' => ComponentStatus::READY]);
    expect($capability->status)->toBe(ComponentStatus::PENDING);

    $component->status = ComponentStatus::READY;
    $component->save();
    $environment->status = EnvironmentStatus::DELETING;
    $environment->save();
    $capability->status = ComponentStatus::READY;
    $capability->save();

    $this->assertDatabaseHas('components', ['id' => $component->id, 'path' => $path, 'status' => ComponentStatus::READY]);
    $this->assertDatabaseHas('environments', ['id' => $environment->id, 'status' => EnvironmentStatus::DELETING]);
    $this->assertDatabaseHas('component_capabilities', ['id' => $capability->id, 'status' => ComponentStatus::READY]);
});

test('application enums expose their display contract', function (VitoEnum $enum, string $text, string $color) {
    expect($enum->getText())->toBe($text)->and($enum->getColor())->toBe($color);
})->with([
    [ComponentType::APP, 'app', 'info'],
    [ComponentType::LOAD_BALANCER, 'load_balancer', 'warning'],
    [ComponentStatus::PENDING, 'pending', 'gray'],
    [ComponentStatus::INSTALLING, 'installing', 'warning'],
    [ComponentStatus::READY, 'ready', 'success'],
    [ComponentStatus::INSTALLATION_FAILED, 'installation_failed', 'danger'],
    [ComponentStatus::DELETING, 'deleting', 'danger'],
    [ComponentStatus::DELETION_FAILED, 'deletion_failed', 'danger'],
    [EnvironmentStatus::READY, 'ready', 'success'],
    [EnvironmentStatus::DELETING, 'deleting', 'danger'],
    [EnvironmentStatus::DELETION_FAILED, 'deletion_failed', 'danger'],
]);
