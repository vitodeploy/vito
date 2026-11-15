<?php

use App\Actions\Component\PurgeServerComponents;
use App\ComponentCapabilities\AbstractComponentCapability;
use App\Enums\ComponentStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Jobs\Service\UninstallJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\CronJob;
use App\Models\Deployment;
use App\Models\DeploymentScript;
use App\Models\Environment;
use App\Models\IsolatedUser;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Site;
use App\Models\Worker;
use App\Plugins\RegisterComponentCapability;
use App\Plugins\RegisterRuntimePreset;
use App\RuntimePresets\AbstractRuntimePreset;
use App\RuntimePresets\NodeJS;
use App\RuntimePresets\PHP;
use App\SiteTypes\PHPBlank;
use App\Support\Testing\SSHFake;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\MySqlConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake();
    Http::fake();
    Event::fake([SocketEvent::class]);
    $application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'node']);
    $environment = Environment::factory()->for($application)->create(['runtime_version' => '20']);
    $this->component = Component::factory()->for($environment)->for($this->server)->create();
});

test('deleting a site preserves its component sibling user and only keeps a needed fpm pool', function (string $runtime, ?string $version, bool $web, bool $keepPool) {
    $site = Site::factory()->create([
        'server_id' => $this->server->id, 'type' => PHPBlank::id(), 'user' => 'shared',
        'domain' => 'shared.test', 'path' => '/home/shared/shared.test', 'php_version' => '8.2',
    ]);
    $this->component->forceFill(['isolated_user_id' => $site->isolated_user_id, 'user' => $site->user])->save();
    $this->component->environment->update(['runtime_version' => $version]);
    $this->component->environment->application->update(['runtime' => $runtime]);
    if ($web) {
        ComponentCapability::factory()->for($this->component)->create(['capability' => 'web']);
    }

    expect($site->userSharedWithSiblings())->toBeTrue()
        ->and($site->siblingsSharingUser()->exists())->toBeFalse()
        ->and($site->fpmPoolSharedWithSiblings())->toBe($keepPool);

    $this->actingAs($this->user)->delete(route('site-settings.destroy', [$this->server, $site]), [
        'domain' => $site->domain,
    ])->assertSessionDoesntHaveErrors();

    SSH::assertNotExecutedContains('userdel');
    if ($keepPool) {
        SSH::assertNotExecutedContains('rm -f /etc/php/8.2/fpm/pool.d/shared.conf');
    } else {
        SSH::assertExecutedContains('rm -f /etc/php/8.2/fpm/pool.d/shared.conf');
    }
    $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    $this->assertDatabaseHas('isolated_users', ['id' => $site->isolated_user_id]);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'isolated_user_id' => $site->isolated_user_id]);
})->with([
    ['php', '8.2', true, true],
    ['laravel', '8.2', true, true],
    ['php', '8.4', true, false],
    ['php', '8.2', false, false],
    ['node', '8.2', true, false],
    ['static', null, true, false],
]);

test('changing site php version preserves the old pool used by a component', function () {
    $site = Site::factory()->create([
        'server_id' => $this->server->id, 'type' => PHPBlank::id(), 'user' => 'shared',
        'domain' => 'shared.test', 'path' => '/home/shared/shared.test', 'php_version' => '8.2',
    ]);
    $this->component->forceFill(['isolated_user_id' => $site->isolated_user_id, 'user' => $site->user])->save();
    $this->component->environment->update(['runtime_version' => '8.2']);
    $this->component->environment->application->update(['runtime' => 'php']);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web']);
    Service::factory()->create([
        'server_id' => $this->server->id, 'type' => 'php', 'name' => 'php', 'version' => '8.4', 'status' => ServiceStatus::READY,
    ]);

    $this->actingAs($this->user)->patch(route('site-settings.update-php-version', [$this->server, $site]), [
        'version' => '8.4',
    ])->assertSessionDoesntHaveErrors();

    SSH::assertNotExecutedContains('rm -f /etc/php/8.2/fpm/pool.d/shared.conf');
    $this->assertDatabaseHas('sites', ['id' => $site->id, 'php_version' => '8.4']);
    $this->assertDatabaseHas('environments', ['id' => $this->component->environment_id, 'runtime_version' => '8.2']);
});

test('isolated user sibling checks exclude only the supplied owner and use fresh relationships', function () {
    $iuser = IsolatedUser::factory()->create(['server_id' => $this->server->id]);
    $this->component->forceFill(['isolated_user_id' => $iuser->id, 'user' => $iuser->username])->save();
    $this->component->environment->update(['runtime_version' => '8.2']);
    $this->component->environment->application->update(['runtime' => 'php']);
    ComponentCapability::factory()->for($this->component)->create();
    $iuser->load('sites', 'components');

    expect($iuser->isUsedByOthers($this->component))->toBeFalse()
        ->and($iuser->fpmPoolUsedByOthers('8.2', $this->component))->toBeFalse();

    $this->site->update(['isolated_user_id' => $iuser->id]);
    expect($this->site->id)->toBe($this->component->id)
        ->and($iuser->isUsedByOthers($this->component))->toBeTrue()
        ->and($iuser->fpmPoolUsedByOthers('8.2', $this->component))->toBeTrue()
        ->and($iuser->isUsedByOthers($this->site))->toBeTrue()
        ->and($iuser->fpmPoolUsedByOthers('8.2', $this->site))->toBeTrue();

    $this->site->update(['isolated_user_id' => null]);
    $sibling = Component::factory()->for($this->component->environment, 'environment')->for($this->server)->create([
        'isolated_user_id' => $iuser->id, 'user' => 'sibling',
    ]);
    ComponentCapability::factory()->for($sibling)->create();
    expect($iuser->isUsedByOthers($this->component))->toBeTrue()
        ->and($iuser->fpmPoolUsedByOthers('8.2', $this->component))->toBeTrue()
        ->and($iuser->fpmPoolUsedByOthers('8.4', $this->component))->toBeFalse();
});

test('unrelated isolated users and servers do not preserve a site user or pool', function () {
    $site = Site::factory()->create(['server_id' => $this->server->id, 'user' => 'alone']);
    $otherUser = IsolatedUser::factory()->create(['server_id' => $this->server->id]);
    $this->component->forceFill(['isolated_user_id' => $otherUser->id, 'user' => $otherUser->username])->save();
    $this->component->environment->update(['runtime_version' => '8.2']);
    $this->component->environment->application->update(['runtime' => 'php']);
    ComponentCapability::factory()->for($this->component)->create();
    $otherServer = Server::factory()->create(['project_id' => $this->server->project_id]);
    $sameNameUser = IsolatedUser::factory()->create(['server_id' => $otherServer->id, 'username' => $site->user]);
    $sibling = Component::factory()->for($this->component->environment, 'environment')->for($otherServer, 'server')->create([
        'isolated_user_id' => $sameNameUser->id, 'user' => $sameNameUser->username,
    ]);
    ComponentCapability::factory()->for($sibling)->create();

    expect($site->userSharedWithSiblings())->toBeFalse()
        ->and($site->fpmPoolSharedWithSiblings())->toBeFalse()
        ->and($this->site->userSharedWithSiblings())->toBeFalse()
        ->and($this->site->fpmPoolSharedWithSiblings())->toBeFalse();
    $site->update(['php_version' => null]);
    expect($site->fpmPoolSharedWithSiblings())->toBeFalse();
});

test('service uninstall rejects dependencies used only by a component', function (string $runtime, ?string $version, ?string $capability, string $serviceType) {
    Queue::fake();
    $this->server->sites()->delete();
    $this->component->environment->application->update(['runtime' => $runtime]);
    $this->component->environment->update(['runtime_version' => $version]);
    if ($capability !== null) {
        ComponentCapability::factory()->for($this->component)->create(['capability' => $capability]);
    }
    $service = $this->server->services()->where('type', $serviceType)->firstOrFail();

    expect($this->server->componentsUsing($service)->modelKeys())->toBe([$this->component->id]);
    $this->actingAs($this->user)->deleteJson(route('services.destroy', [$this->server, $service]))
        ->assertUnprocessable()->assertJsonValidationErrors('service');

    $this->assertDatabaseHas('services', ['id' => $service->id, 'status' => ServiceStatus::READY]);
    Queue::assertNothingPushed();
    expect(SSH::getExecutedCommands())->toBe([]);
})->with([
    ['php', '8.2', 'web', 'php'],
    ['php', '8.2', 'worker', 'php'],
    ['php', '8.2', null, 'php'],
    ['node', '20', 'web', 'webserver'],
    ['static', null, 'web', 'webserver'],
]);

test('unused service versions and other server components do not block uninstall', function (string $runtime, ?string $version, ?string $capability, string $serviceType, bool $otherServer) {
    Queue::fake();
    $this->server->sites()->delete();
    $this->component->environment->application->update(['runtime' => $runtime]);
    $this->component->environment->update(['runtime_version' => $version]);
    if ($otherServer) {
        $server = Server::factory()->create(['project_id' => $this->server->project_id]);
        $this->component->update(['server_id' => $server->id]);
    }
    if ($capability !== null) {
        ComponentCapability::factory()->for($this->component)->create(['capability' => $capability]);
    }
    $service = $this->server->services()->where('type', $serviceType)->firstOrFail();

    expect($this->server->componentsUsing($service))->toBeEmpty();
    $this->actingAs($this->user)->deleteJson(route('services.destroy', [$this->server, $service]))
        ->assertRedirect()->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('services', ['id' => $service->id, 'status' => ServiceStatus::UNINSTALLING]);
    Queue::assertPushed(UninstallJob::class);
})->with([
    ['php', '8.4', 'web', 'php', false],
    ['node', '20', 'web', 'nodejs', false],
    ['node', '20', 'worker', 'webserver', false],
    ['static', null, null, 'webserver', false],
    ['php', '8.2', 'web', 'php', true],
    ['node', '20', 'web', 'webserver', true],
]);

test('plugin runtime service dependencies protect the declared system node version', function (?string $requiredVersion, bool $blocked) {
    $preset = new class extends NodeJS
    {
        public static function id(): string
        {
            return 'system-node';
        }

        public function requiredServices(string $version): array
        {
            return ['nodejs' => $version === '' ? null : $version];
        }
    };
    RegisterRuntimePreset::make($preset::id())->handler($preset::class)->register();
    $this->component->environment->application->update(['runtime' => $preset::id()]);
    $this->component->environment->update(['runtime_version' => $requiredVersion]);
    Queue::fake();
    $service = $this->server->nodejs();

    $response = $this->actingAs($this->user)->deleteJson(route('services.destroy', [$this->server, $service]));

    if ($blocked) {
        $response->assertUnprocessable()->assertJsonValidationErrors(['service' => 'Some components are using this NodeJS version.']);
        Queue::assertNothingPushed();
    } else {
        $response->assertRedirect()->assertSessionDoesntHaveErrors();
        Queue::assertPushed(UninstallJob::class);
    }
})->with([['20', true], [null, true], ['22', false]]);

test('plugin capability service dependencies participate in the same service guard', function () {
    $capability = new class(new ComponentCapability) extends AbstractComponentCapability
    {
        public static function id(): string
        {
            return 'node-build';
        }

        public static function label(): string
        {
            return 'Node build';
        }

        public function requiredServices(AbstractRuntimePreset $preset): array
        {
            return ['nodejs'];
        }
    };
    RegisterComponentCapability::make($capability::id())->handler($capability::class)->register();
    ComponentCapability::factory()->for($this->component)->create(['capability' => $capability::id()]);

    $this->actingAs($this->user)->deleteJson(route('services.destroy', [$this->server, $this->server->nodejs()]))
        ->assertUnprocessable()->assertJsonValidationErrors('service');
    expect(SSH::getExecutedCommands())->toBe([]);
});

test('fpm capability dependencies respect the environment version without preset requirements', function (string $version, bool $used) {
    $preset = new class extends PHP
    {
        public static function id(): string
        {
            return 'fpm-only';
        }

        public function requiredServices(string $version): array
        {
            return [];
        }
    };
    RegisterRuntimePreset::make($preset::id())->handler($preset::class)->register();
    $this->component->environment->application->update(['runtime' => $preset::id()]);
    $this->component->environment->update(['runtime_version' => $version]);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web']);

    expect($this->server->componentsUsing($this->server->php('8.2'))->isNotEmpty())->toBe($used);
})->with([['8.2', true], ['8.4', false]]);

test('component workers already protect their process manager from uninstall', function () {
    Worker::factory()->create(['server_id' => $this->server->id, 'component_id' => $this->component->id]);
    $service = $this->server->services()->where('type', 'process_manager')->firstOrFail();

    $this->actingAs($this->user)->deleteJson(route('services.destroy', [$this->server, $service]))
        ->assertUnprocessable()->assertJsonValidationErrors('service');
    expect(SSH::getExecutedCommands())->toBe([]);
});

test('server deletion purges only its components and their owned rows without remote teardown', function () {
    $environment = $this->component->environment;
    $environment->forceFill(['primary_component_id' => $this->component->id])->save();
    $otherServer = Server::factory()->create(['project_id' => $this->server->project_id]);
    $survivor = Component::factory()->for($environment)->for($otherServer, 'server')->create();
    $capability = ComponentCapability::factory()->for($this->component)->create();
    $worker = Worker::factory()->create(['server_id' => $this->server->id, 'component_id' => $this->component->id]);
    $cron = CronJob::factory()->create(['server_id' => $this->server->id, 'component_id' => $this->component->id]);
    $deployment = Deployment::factory()->create(['site_id' => null, 'component_id' => $this->component->id, 'deployment_script_id' => null, 'log_id' => null]);
    $script = DeploymentScript::factory()->create(['site_id' => null, 'component_id' => $this->component->id]);
    $environmentScript = DeploymentScript::factory()->create(['site_id' => null, 'environment_id' => $environment->id]);
    $survivorWorker = Worker::factory()->create(['server_id' => $otherServer->id, 'component_id' => $survivor->id]);
    $otherEnvironment = Environment::factory()->for($environment->application, 'application')->create();
    Component::factory()->for($otherEnvironment, 'environment')->for($this->server)->create();
    Component::factory()->for($environment)->for($this->server)->create(['user' => 'other']);

    $this->partialMock(PurgeServerComponents::class)->shouldReceive('handle')->once()
        ->with(['load_balancer_servers' => [], 'environments' => [$environment->id, $otherEnvironment->id], 'keys' => []])
        ->andReturnUsing(function () use ($environment): void {
            $this->assertDatabaseMissing('servers', ['id' => $this->server->id]);
            $this->assertDatabaseHas('environments', ['id' => $environment->id, 'primary_component_id' => null]);
        });

    $this->actingAs($this->user)->delete(route('servers.destroy', $this->server), ['name' => $this->server->name])
        ->assertSessionDoesntHaveErrors();

    foreach ([$this->component, $capability, $worker, $cron, $script] as $deleted) {
        $this->assertDatabaseMissing($deleted->getTable(), ['id' => $deleted->id]);
    }
    $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'component_id' => null]);
    foreach ([$survivor, $survivorWorker, $environmentScript] as $remaining) {
        $this->assertDatabaseHas($remaining->getTable(), ['id' => $remaining->id]);
    }
    expect(SSH::getExecutedCommands())->toBe([]);
    Http::assertNothingSent();
});

test('component purges roll back with a failed server cleanup', function () {
    $environment = $this->component->environment;
    $environment->forceFill(['primary_component_id' => $this->component->id])->save();
    $capability = ComponentCapability::factory()->for($this->component)->create();
    ServerLog::factory()->create(['server_id' => $this->server->id]);
    Event::listen('eloquent.deleting: '.ServerLog::class, function (): void {
        throw new RuntimeException('Cleanup failed');
    });
    $this->partialMock(PurgeServerComponents::class)->shouldNotReceive('handle');

    expect(fn () => $this->server->delete())->toThrow(RuntimeException::class, 'Cleanup failed');

    foreach ([$this->server, $this->component, $capability, $this->site] as $preserved) {
        $this->assertDatabaseHas($preserved->getTable(), ['id' => $preserved->id]);
    }
    $this->assertDatabaseHas('environments', ['id' => $environment->id, 'primary_component_id' => $this->component->id]);
    expect(SSH::getExecutedCommands())->toBe([]);
});

test('failed server deletion after cleanup restores components and primary references', function (bool $throughProject) {
    $environment = $this->component->environment;
    $environment->forceFill(['primary_component_id' => $this->component->id])->save();
    $capability = ComponentCapability::factory()->for($this->component)->create();
    $worker = Worker::factory()->create(['server_id' => $this->server->id, 'component_id' => $this->component->id]);
    Event::listen('eloquent.deleting: '.Server::class, function (): void {
        throw new RuntimeException('Late deletion failure');
    });
    $this->partialMock(PurgeServerComponents::class)->shouldNotReceive('handle');
    $owner = $throughProject ? $this->server->project : $this->server;

    expect(fn () => $owner->delete())->toThrow(RuntimeException::class, 'Late deletion failure');

    foreach ([$this->server, $this->component, $capability, $worker, $this->site] as $preserved) {
        $this->assertDatabaseHas($preserved->getTable(), ['id' => $preserved->id]);
    }
    $this->assertDatabaseHas('environments', ['id' => $environment->id, 'primary_component_id' => $this->component->id]);
})->with([false, true]);

test('server purge locks placement before capturing components in the deletion transaction', function () {
    $locked = false;
    $depth = DB::transactionLevel();
    Server::addGlobalScope('verify-purge-lock', function (Builder $query) use (&$locked, $depth): void {
        if ($query->getQuery()->lock === true) {
            expect(DB::transactionLevel())->toBeGreaterThan($depth);
            $locked = true;
        }
    });
    Event::listen('eloquent.retrieved: '.Component::class, function () use (&$locked): void {
        expect($locked)->toBeTrue();
    });

    $this->server->delete();

    expect($locked)->toBeTrue();
    $this->assertDatabaseMissing('servers', ['id' => $this->server->id]);
    $this->assertDatabaseMissing('components', ['id' => $this->component->id]);
});

test('server cleanup deadlocks preserve the failure and reset transaction depth', function () {
    $pdo = Mockery::mock(PDO::class);
    $pdo->shouldReceive('beginTransaction')->once()->andReturnTrue();
    $pdo->shouldReceive('inTransaction')->andReturnFalse();
    $pdo->shouldReceive('exec')->andReturnUsing(function (string $statement): int {
        if (str_starts_with($statement, 'ROLLBACK')) {
            throw new PDOException('Savepoint no longer exists');
        }

        return 0;
    });
    $connection = new MySqlConnection($pdo);
    $deadlock = new DeadlockException('Deadlock found when trying to get lock', 40001);
    $this->mock(PurgeServerComponents::class)->shouldReceive('purge')->once()->andThrow($deadlock);
    $database = DB::getFacadeRoot();
    DB::swap($connection);

    try {
        expect(fn () => $this->server->delete())->toThrow(DeadlockException::class, $deadlock->getMessage());
        expect($connection->transactionLevel())->toBe(0);
    } finally {
        DB::swap($database);
    }
});

test('cancelled server deletion restores components and primary references', function () {
    $environment = $this->component->environment;
    $environment->forceFill(['primary_component_id' => $this->component->id])->save();
    Event::listen('eloquent.deleting: '.Server::class, fn (): bool => false);

    expect($this->server->delete())->toBeFalse();

    $this->assertDatabaseHas('servers', ['id' => $this->server->id]);
    $this->assertDatabaseHas('components', ['id' => $this->component->id]);
    $this->assertDatabaseHas('environments', ['id' => $environment->id, 'primary_component_id' => $this->component->id]);
});

test('preset and fpm web dependencies can protect different versions of the same service', function (string $version) {
    $preset = new class extends PHP
    {
        public static function id(): string
        {
            return 'mixed-php';
        }

        public function requiredServices(string $version): array
        {
            return ['php' => '8.2'];
        }
    };
    RegisterRuntimePreset::make($preset::id())->handler($preset::class)->register();
    $this->component->environment->application->update(['runtime' => $preset::id()]);
    $this->component->environment->update(['runtime_version' => '8.4']);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web']);
    Service::factory()->create([
        'server_id' => $this->server->id, 'type' => 'php', 'name' => 'php', 'version' => '8.4', 'status' => ServiceStatus::READY,
    ]);

    expect($this->server->componentsUsing($this->server->php($version))->modelKeys())->toBe([$this->component->id]);
})->with(['8.2', '8.4']);

test('non-web capability dependencies are independent of the fpm runtime version', function () {
    $capability = new class(new ComponentCapability) extends AbstractComponentCapability
    {
        public static function id(): string
        {
            return 'php-tooling';
        }

        public static function label(): string
        {
            return 'PHP tooling';
        }

        public function requiredServices(AbstractRuntimePreset $preset): array
        {
            return ['php'];
        }
    };
    RegisterComponentCapability::make($capability::id())->handler($capability::class)->register();
    $this->component->environment->application->update(['runtime' => 'php']);
    $this->component->environment->update(['runtime_version' => '8.4']);
    ComponentCapability::factory()->for($this->component)->create(['capability' => $capability::id()]);

    expect($this->server->componentsUsing($this->server->php('8.2'))->modelKeys())->toBe([$this->component->id]);
});

test('site provisioning ensures its pool even when an uninstalled component shares it', function (string $status) {
    $site = Site::factory()->create([
        'server_id' => $this->server->id, 'type' => PHPBlank::id(), 'user' => 'shared',
        'domain' => 'shared.test', 'path' => '/home/shared/shared.test', 'php_version' => '8.2',
    ]);
    $this->component->forceFill(['isolated_user_id' => $site->isolated_user_id, 'user' => $site->user])->save();
    $this->component->status = ComponentStatus::from($status);
    $this->component->save();
    $this->component->environment->application->update(['runtime' => 'php']);
    $this->component->environment->update(['runtime_version' => '8.2']);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web']);
    $ssh = Mockery::mock(SSHFake::class, [null])->makePartial();
    SSH::swap($ssh);
    $ssh->shouldReceive('exec')
        ->withArgs(fn ($command): bool => str_contains((string) $command, 'test -f /etc/php/8.2/fpm/pool.d/shared.conf'))
        ->once()->andThrow(new SSHCommandError);

    $type = $site->type();
    (new ReflectionMethod($type, 'isolate'))->invoke($type);

    expect($ssh->getUploadedContent())->toContain('[shared]');
    SSH::assertExecutedContains('restart php8.2-fpm');
})->with(['pending', 'installation_failed']);

test('project deletion purges components across its servers without touching another project', function () {
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $application = Application::factory()->for($project)->create();
    $environment = Environment::factory()->for($application)->create();
    $components = collect();
    foreach (range(1, 2) as $index) {
        $server = Server::factory()->for($project)->create(['user_id' => $this->user->id]);
        $components->push(Component::factory()->for($environment)->for($server)->create());
    }
    $environment->forceFill(['primary_component_id' => $components->first()->id])->save();

    $this->actingAs($this->user)->delete(route('projects.destroy', $project), ['name' => $project->name])
        ->assertSessionDoesntHaveErrors();

    foreach ([$project, $application, $environment, ...$components] as $deleted) {
        $this->assertDatabaseMissing($deleted->getTable(), ['id' => $deleted->id]);
    }
    $this->assertDatabaseHas('components', ['id' => $this->component->id]);
    $this->assertDatabaseHas('servers', ['id' => $this->server->id]);
    expect(SSH::getExecutedCommands())->toBe([]);
    Http::assertNothingSent();
});
