<?php

use App\Contracts\VitoEnum;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentStatus;
use App\Enums\WorkerStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Jobs\Site\DeployJob;
use App\Jobs\Worker\EditJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\CronJob;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Deployment;
use App\Models\DeploymentScript;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\EnvironmentDomain;
use App\Models\FirewallRule;
use App\Models\GitHook;
use App\Models\HostedDomain;
use App\Models\IsolatedUser;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Site;
use App\Models\Ssl;
use App\Models\Worker;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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
    $component->forceFill(['path' => $path])->save();

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

test('component deployment ownership cannot be mass assigned', function () {
    $component = Component::factory()->create(['server_id' => $this->server->id]);
    $deployment = Deployment::factory()->create(['site_id' => $this->site->id]);
    $deployment->fill(['component_id' => $component->id]);
    $deployment->save();
    $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'site_id' => $this->site->id, 'component_id' => null]);
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
    $run = EnvironmentDeployment::factory()->for($environment)->create();
    $deployment = Deployment::factory()->create(['site_id' => null, 'component_id' => $component->id, 'deployment_script_id' => null, 'environment_deployment_id' => $run->id]);
    $script = DeploymentScript::factory()->create(['site_id' => null, 'component_id' => $component->id]);
    $environmentScript = DeploymentScript::factory()->create(['site_id' => null, 'environment_id' => $environment->id]);
    $owner = match ($parent) {
        'component' => $component,
        'environment' => $environment,
        'application' => $environment->application,
        'project' => $environment->application->project,
    };

    $owner->newQuery()->whereKey($owner->id)->delete();

    foreach ([$component, $capability, $worker, $cron, $script] as $deleted) {
        $this->assertDatabaseMissing($deleted->getTable(), ['id' => $deleted->id]);
    }
    if ($parent === 'component') {
        $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'component_id' => null]);
    } else {
        $this->assertDatabaseMissing('deployments', ['id' => $deployment->id]);
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
    $siteSsl = Ssl::factory()->create(['site_id' => $this->site->id]);
    $serverSsl = Ssl::factory()->serverLevel()->for($this->server)->create();
    $environmentSsl = Ssl::factory()->create(['site_id' => null, 'server_id' => $component->server_id, 'environment_id' => $component->environment_id]);
    EnvironmentDomain::factory()->for($component)->create(['ssl_id' => $environmentSsl->id]);
    $migration = require database_path('migrations/2026_10_17_171647_create_applications_tables.php');
    $deploymentsMigration = require database_path('migrations/2026_10_18_160459_create_environment_deployments_table.php');
    $operationsMigration = require database_path('migrations/2026_10_18_223613_add_environment_deployment_operations.php');
    $domainsMigration = require database_path('migrations/2026_10_19_085705_create_environment_domains.php');

    $domainsMigration->down();
    expect(Schema::hasTable('environment_domains'))->toBeFalse()
        ->and(Schema::hasColumn('ssls', 'environment_id'))->toBeFalse();
    $this->assertDatabaseMissing('ssls', ['id' => $environmentSsl->id]);
    $operationsMigration->down();
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
    $operationsMigration->up();
    $domainsMigration->up();
    expect(Schema::hasTable('environment_domains'))->toBeTrue()
        ->and(Schema::hasColumn('ssls', 'environment_id'))->toBeTrue()
        ->and(Schema::hasColumn('environments', 'live_run_id'))->toBeTrue()
        ->and(Schema::hasColumn('deployments', 'previous_deployment_id'))->toBeTrue();
    $this->assertDatabaseHas('ssls', ['id' => $serverSsl->id, 'site_id' => null, 'environment_id' => null]);

    foreach ([$siteDeployment, $siteScript, $siteWorker, $siteCron, $siteSsl] as $preserved) {
        $this->assertDatabaseHas($preserved->getTable(), ['id' => $preserved->id, 'site_id' => $this->site->id]);
    }
    expect(Component::query()->count())->toBe(0)
        ->and(SSH::getExecutedCommands())->toBe([]);
});

test('all application migrations preserve a populated legacy SQLite schema and its web and api workflows', function () {
    expect(app()->environment('testing'))->toBeTrue();
    /** SQLite schema changes must run outside the test transaction, as they do in the Migrator. */
    $this->server->getConnection()->commit();
    RefreshDatabaseState::$migrated = false;
    SSH::fake('legacy worker log');
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response([])]);
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $migrationFiles = collect([
        '2026_10_17_171647_create_applications_tables.php',
        '2026_10_17_224408_add_component_lifecycle_fields.php',
        '2026_10_18_083748_add_component_capability_process_ownership.php',
        '2026_10_18_133728_create_environment_variables_table.php',
        '2026_10_18_160459_create_environment_deployments_table.php',
        '2026_10_18_223613_add_environment_deployment_operations.php',
        '2026_10_19_085705_create_environment_domains.php',
        '2026_10_19_141435_create_component_backends.php',
        '2026_10_19_154937_create_environment_attachments.php',
        '2026_10_23_131437_backfill_worker_log_paths.php',
    ])->map(fn (string $file): string => database_path('migrations/'.$file));
    $migrator = app('migrator');
    foreach ($migrationFiles->reverse() as $file) {
        (require $file)->down();
        $migrator->getRepository()->delete((object) ['migration' => basename($file, '.php')]);
    }

    $newTables = ['applications', 'environments', 'components', 'component_capabilities', 'environment_variables', 'environment_deployments', 'environment_domains', 'component_backends', 'environment_attachments'];
    foreach ($newTables as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
    foreach ([
        'deployments' => ['component_id', 'environment_deployment_id', 'previous_deployment_id', 'current_stage', 'release_started', 'component_name', 'server_name'],
        'deployment_scripts' => ['component_id', 'environment_id'],
        'workers' => ['component_id', 'component_capability_id'],
        'cron_jobs' => ['component_id', 'component_capability_id'],
        'git_hooks' => ['repository'],
        'ssls' => ['environment_id'],
        'database_users' => ['environment_attachment_id'],
        'firewall_rules' => ['environment_attachment_id', 'component_managed'],
    ] as $table => $columns) {
        foreach ($columns as $column) {
            expect(Schema::hasColumn($table, $column))->toBeFalse();
        }
    }
    foreach (['deployments', 'deployment_scripts', 'git_hooks'] as $table) {
        expect(collect(Schema::getColumns($table))->firstWhere('name', 'site_id')['nullable'])->toBeFalse();
    }

    $source = $this->site->sourceControl;
    $source->update(['provider_data' => ['token' => 'legacy-source-secret']]);
    $this->server->update(['authentication' => ['password' => 'legacy-server-secret']]);
    $redis = $this->server->memoryDatabase();
    $redis->forceFill(['secret' => 'legacy-redis-secret', 'type_data' => ['networking' => false]])->save();
    $this->site->jsonUpdate('type_data', 'deploy_key_id', 'legacy-key-id');
    $site = Site::factory()->create([
        'server_id' => $this->server->id, 'source_control_id' => $source->id, 'repository' => 'owner/legacy-isolated',
        'domain' => 'legacy-isolated.test', 'user' => 'legacyuser', 'path' => '/home/legacyuser/legacy-isolated.test',
        'ssh_key' => 'ssh-rsa legacy-site-key', 'type_data' => ['deploy_key_id' => 'isolated-key-id', 'env_path' => '.env'],
    ]);
    $script = $site->deploymentScript;
    $log = ServerLog::factory()->create(['server_id' => $this->server->id, 'site_id' => $site->id]);
    $deployment = Deployment::factory()->create([
        'site_id' => $site->id, 'deployment_script_id' => $script->id, 'log_id' => $log->id,
        'release' => '20261016000000', 'active' => true, 'commit_id' => str_repeat('a', 40),
    ]);
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id, 'site_id' => $site->id, 'user' => $site->user, 'status' => WorkerStatus::RUNNING,
        'environment' => [['key' => 'LEGACY_TOKEN', 'value' => 'legacy-worker-secret', 'is_secret' => true]],
    ]);
    $rootWorker = Worker::factory()->create(['server_id' => $this->server->id, 'user' => 'root', 'status' => WorkerStatus::RUNNING]);
    $customWorker = Worker::factory()->create([
        'server_id' => $this->server->id, 'user' => 'vito', 'status' => WorkerStatus::RUNNING, 'stdout_logfile' => '/home/vito/existing-worker.log',
    ]);
    $cron = CronJob::factory()->create(['server_id' => $this->server->id, 'site_id' => $site->id, 'user' => $site->user, 'command' => 'echo legacy-site-cron']);
    $rootCron = CronJob::factory()->create(['server_id' => $this->server->id, 'user' => 'root', 'command' => 'echo legacy-server-cron']);
    $ssl = Ssl::factory()->create(['site_id' => $site->id, 'certificate' => 'legacy-certificate', 'pk' => 'legacy-private-key']);
    $serverSsl = Ssl::factory()->serverLevel()->create(['server_id' => $this->server->id]);
    $host = HostedDomain::factory()->primary()->create(['site_id' => $site->id, 'domain' => $site->domain, 'ssl_id' => $ssl->id]);
    $hook = GitHook::factory()->create(['site_id' => $site->id, 'source_control_id' => $source->id, 'actions' => ['deploy']]);
    $database = Database::factory()->create(['server_id' => $this->server->id, 'name' => 'legacy_database']);
    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server->id, 'username' => 'legacy_database_user', 'password' => 'legacy-database-secret', 'databases' => [$database->name],
    ]);
    $rule = FirewallRule::factory()->create(['server_id' => $this->server->id, 'port' => '443']);
    $token = $this->user->createToken('legacy-api', ['read', 'write', 'project:'.$this->server->project_id]);
    $records = [$this->server, $this->server->project, $this->user, $this->site, $source, $redis, $site, $site->isolatedUser, $script, $log, $deployment, $worker, $rootWorker, $customWorker, $cron, $rootCron, $ssl, $serverSsl, $host, $hook, $database, $databaseUser, $rule, $token->accessToken];
    $snapshots = collect($records)->map(fn ($record): array => ['model' => $record, 'attributes' => $record->fresh()->getRawOriginal()]);
    $keyPaths = $this->server->sshKey();
    expect(File::size($keyPaths['private_key_path']))->toBeGreaterThan(0)
        ->and(File::size($keyPaths['public_key_path']))->toBeGreaterThan(0);
    $keyHashes = collect([$keyPaths['private_key_path'], $keyPaths['public_key_path']])->mapWithKeys(fn (string $path): array => [$path => hash_file('sha256', $path)]);

    Event::fake([SocketEvent::class]);
    expect($migrator->run($migrationFiles->all()))->toBe($migrationFiles->all());

    expect($this->server->getConnection()->scalar('PRAGMA foreign_keys'))->toBe(1);
    foreach ($newTables as $table) {
        $this->assertDatabaseCount($table, 0);
    }
    foreach ($snapshots as $snapshot) {
        $record = $snapshot['model'];
        $expected = $snapshot['attributes'];
        if ($record instanceof Worker && $expected['stdout_logfile'] === null) {
            $home = $expected['user'] === 'root' ? '/root' : '/home/'.$expected['user'];
            $expected['stdout_logfile'] = $home.'/.logs/workers/'.$record->id.'.log';
        }
        expect($record->fresh()->getRawOriginal())->toMatchArray($expected);
    }
    foreach ($keyHashes as $path => $hash) {
        expect(hash_file('sha256', $path))->toBe($hash);
    }
    foreach ([$deployment, $script, $worker, $cron] as $record) {
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id, 'site_id' => $site->id, 'component_id' => null]);
    }
    $this->assertDatabaseHas('git_hooks', ['id' => $hook->id, 'site_id' => $site->id, 'repository' => null]);
    $this->assertDatabaseHas('ssls', ['id' => $ssl->id, 'site_id' => $site->id, 'environment_id' => null]);
    $this->assertDatabaseHas('database_users', ['id' => $databaseUser->id, 'environment_attachment_id' => null]);
    $this->assertDatabaseHas('firewall_rules', ['id' => $rule->id, 'component_managed' => false, 'environment_attachment_id' => null]);
    expect($source->fresh()->provider_data['token'])->toBe('legacy-source-secret')
        ->and($this->server->fresh()->authentication['password'])->toBe('legacy-server-secret')
        ->and($redis->fresh()->secret)->toBe('legacy-redis-secret')
        ->and($databaseUser->fresh()->password)->toBe('legacy-database-secret')
        ->and($ssl->fresh()->pk)->toBe('legacy-private-key')
        ->and($worker->fresh()->environment[0]['value'])->toBe('legacy-worker-secret')
        ->and(SSH::getExecutedCommands())->toBe([]);
    Http::assertNothingSent();
    Queue::assertNothingPushed();
    Event::assertNotDispatched(SocketEvent::class);

    $serverRoute = ['project' => $this->server->project_id, 'server' => $this->server];
    $route = [...$serverRoute, 'site' => $site];
    $this->withToken($token->plainTextToken)->getJson(route('api.projects.servers.sites.show', $route))
        ->assertOk()->assertJsonPath('id', $site->id)->assertJsonPath('path', $site->path)->assertJsonPath('type_data.deploy_key_id', 'isolated-key-id');
    $this->getJson(route('api.projects.servers.sites.workers.show', [...$route, 'worker' => $worker]))
        ->assertOk()->assertJsonPath('site_id', $site->id)->assertJsonPath('status', 'running')->assertJsonMissingPath('component_id');
    $this->getJson(route('api.projects.servers.sites.cron-jobs.show', [...$route, 'cronJob' => $cron]))
        ->assertOk()->assertJsonPath('command', 'echo legacy-site-cron');
    $this->getJson(route('api.projects.servers.databases.show', [...$serverRoute, 'database' => $database]))
        ->assertOk()->assertJsonPath('name', 'legacy_database');
    $this->getJson(route('api.projects.servers.ssls.show', [...$serverRoute, 'ssl' => $serverSsl]))
        ->assertOk()->assertJsonPath('id', $serverSsl->id)->assertJsonMissingPath('pk');
    $this->getJson(route('api.projects.servers.workers.logs', [...$serverRoute, 'worker' => $worker]))
        ->assertOk()->assertJsonPath('logs', 'legacy worker log');
    SSH::assertExecutedContains('/home/legacyuser/.logs/workers/'.$worker->id.'.log');

    $this->putJson(route('api.projects.servers.workers.update', [...$route, 'worker' => $worker]), [
        'name' => $worker->name, 'command' => $worker->command, 'user' => $worker->user,
        'auto_start' => true, 'auto_restart' => true, 'numprocs' => 2,
    ])->assertOk();
    Queue::assertPushed(EditJob::class);
    Queue::pushed(EditJob::class)->sole()->handle();
    $this->assertDatabaseHas('workers', ['id' => $worker->id, 'site_id' => $site->id, 'component_id' => null, 'numprocs' => 2, 'status' => WorkerStatus::RUNNING]);
    $this->postJson(route('api.projects.servers.cron-jobs.create', $serverRoute), [
        'name' => 'After upgrade', 'user' => 'root', 'command' => 'echo after-upgrade', 'frequency' => '0 * * * *',
    ])->assertSuccessful()->assertJsonPath('status', 'ready')->assertJsonPath('site_id', null);
    SSH::assertExecutedContains('echo legacy-server-cron');
    SSH::assertExecutedContains('echo after-upgrade');
    $this->postJson(route('api.projects.servers.sites.deploy', $route))->assertSuccessful()->assertJsonPath('site_id', $site->id);
    Queue::assertPushed(DeployJob::class);
    $this->assertDatabaseHas('deployments', ['site_id' => $site->id, 'component_id' => null, 'status' => 'deploying']);
    Queue::pushed(DeployJob::class)->sole()->handle();
    $this->assertDatabaseHas('deployments', ['site_id' => $site->id, 'component_id' => null, 'status' => 'finished', 'active' => true]);
    $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'active' => false]);

    $this->actingAs($this->user)->get(route('site-settings', [$this->server, $site]))->assertOk();
    $this->get(route('workers.site', [$this->server, $site]))->assertOk();
    $this->get(route('cronjobs.site', [$this->server, $site]))->assertOk();
    $this->patch(route('site-settings.update-web-directory', [$this->server, $site]), ['web_directory' => 'public-upgraded'])
        ->assertRedirect()->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('sites', ['id' => $site->id, 'web_directory' => 'public-upgraded', 'path' => $site->path, 'source_control_id' => $source->id]);
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
