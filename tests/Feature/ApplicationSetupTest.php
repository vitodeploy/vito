<?php

use App\Actions\Application\ValidateApplication;
use App\Actions\Component\GetComponentOptions;
use App\Actions\Environment\GetEnvironmentOverview;
use App\Actions\Environment\UpdateEnvironment;
use App\Actions\EnvironmentAttachment\ValidateEnvironmentResource;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Actions\Site\Tooling\AssertToolingNotUsedByComponents;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentAttachmentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Exceptions\FailedToDeployGitHook;
use App\Facades\SSH;
use App\Helpers\ComponentShell;
use App\Jobs\Component\InstallComponentJob;
use App\Jobs\EnvironmentAttachment\SyncEnvironmentResourceAccessJob;
use App\Jobs\EnvironmentDeployment\StartEnvironmentDeploymentJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Models\GitHook;
use App\Models\Project;
use App\Models\SourceControl;
use App\RuntimePresets\Laravel;
use App\SourceControlProviders\SupportsApplications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake();
    Queue::fake();
    Event::fake([SocketEvent::class]);
    Http::preventStrayRequests();
    $this->actingAs($this->user);
    $this->sourceControl = SourceControl::factory()->github()->create(['user_id' => $this->user->id, 'project_id' => $this->user->current_project_id]);
    $this->files = ['composer.json' => '{"require":{"php":"^8.3","laravel/framework":"^13.0"}}'];
    $this->hookResponse = Http::response(['id' => 77], 201);
    Http::fake([
        'api.github.com/repos/organization/repository/contents/*' => function (Request $request) {
            $path = substr(parse_url($request->url(), PHP_URL_PATH), strlen('/repos/organization/repository/contents/'));

            return isset($this->files[$path]) ? Http::response($this->files[$path]) : Http::response([], 404);
        },
        'api.github.com/repos/organization/repository/hooks' => fn () => $this->hookResponse,
        'api.github.com/repos/organization/repository/keys' => Http::response(['id' => 5], 201),
        'api.github.com/repos/organization/broken/*' => Http::response(['message' => 'boom'], 500),
        'api.github.com/repos/organization/repository/commits/*' => Http::response(['sha' => str_repeat('a', 40), 'commit' => ['message' => 'Initial']]),
        'api.github.com/repos/organization/repository' => Http::response(['default_branch' => 'trunk']),
    ]);
    $this->input = [
        'name' => 'shop',
        'source_control_id' => $this->sourceControl->id,
        'repository' => 'organization/repository',
        'runtime' => 'laravel',
        'environment' => ['name' => 'Production', 'branch' => 'main', 'runtime_version' => $this->server->php()->version],
        'server_id' => $this->server->id,
    ];
    $this->appKey = 'base64:'.base64_encode(str_repeat('a', 32));
    $this->detect = fn (array $query = []) => $this->getJson(route('applications.detect', [
        'source_control_id' => $this->sourceControl->id, 'repository' => 'organization/repository', ...$query,
    ]));
});

test('detection suggests the default branch runtime and compatible versions', function () {
    ($this->detect)()->assertSuccessful()->assertExactJson([
        'branch' => 'trunk', 'runtime' => 'laravel', 'versions' => ['8.5', '8.4', '8.3'],
    ]);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/contents/composer.json?ref=trunk')
        && $request->header('Accept') === ['application/vnd.github.raw+json']);
});

test('detection prefers the node package manager preset without changing existing runtimes', function (array $files, string $runtime, array $versions) {
    $this->files = $files;

    ($this->detect)()->assertSuccessful()->assertExactJson([
        'branch' => 'trunk', 'runtime' => $runtime, 'versions' => $versions,
    ]);
})->with([
    'pnpm lockfile' => [['package.json' => '{"engines":{"node":"^22"}}', 'pnpm-lock.yaml' => 'lockfileVersion: 9.0'], 'pnpm', ['22']],
    'yarn lockfile' => [['package.json' => '{"engines":{"node":">=23"}}', 'yarn.lock' => '# yarn lockfile v1'], 'yarn', ['24', '23']],
    'pnpm metadata wins over stale locks' => [['package.json' => '{"packageManager":"pnpm@10.0.0"}', 'yarn.lock' => '', 'bun.lock' => ''], 'pnpm', ['24', '23', '22']],
    'yarn metadata wins over stale locks' => [['package.json' => '{"packageManager":"yarn@4.9.2"}', 'pnpm-lock.yaml' => '', 'bun.lock' => ''], 'yarn', ['24', '23', '22']],
    'npm metadata wins over stale locks' => [['package.json' => '{"packageManager":"npm@11.0.0"}', 'pnpm-lock.yaml' => '', 'yarn.lock' => ''], 'node', ['24', '23', '22']],
    'npm without metadata' => [['package.json' => '{}', 'package-lock.json' => '{}'], 'node', ['24', '23', '22']],
    'bun lockfile' => [['package.json' => '{}', 'bun.lock' => '{}'], 'bun', ['1.2', '1.1', '1.0']],
    'laravel keeps priority' => [['composer.json' => '{"require":{"php":"^8.4","laravel/framework":"^13.0"}}', 'package.json' => '{"packageManager":"pnpm@10.0.0"}', 'pnpm-lock.yaml' => ''], 'laravel', ['8.5', '8.4']],
    'php keeps priority' => [['composer.json' => '{"require":{"php":"^8.4"}}', 'package.json' => '{"packageManager":"yarn@4.9.2"}', 'yarn.lock' => ''], 'php', ['8.5', '8.4']],
]);

test('detection reads the requested branch and leaves unknown repositories to the user', function () {
    $this->files = [];

    ($this->detect)(['branch' => 'feature/x'])->assertSuccessful()->assertExactJson(['branch' => 'feature/x', 'runtime' => null, 'versions' => []]);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'ref=feature%2Fx'));
    Http::assertNotSent(fn (Request $request): bool => $request->url() === 'https://api.github.com/repos/organization/repository');
});

test('detection survives provider failures', function () {
    ($this->detect)(['branch' => 'main', 'repository' => 'organization/broken'])->assertSuccessful()->assertExactJson(['branch' => 'main', 'runtime' => null, 'versions' => []]);
});

test('detection validates input and enforces the project boundary', function (array $query, string $field) {
    ($this->detect)($query)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    [['repository' => '../secrets/repo'], 'repository'],
    [['repository' => ''], 'repository'],
    [['branch' => '-upload-pack'], 'branch'],
    [['branch' => 'a b'], 'branch'],
    [['source_control_id' => 99999], 'source_control_id'],
]);

test('detection rejects other projects source controls and read only members', function () {
    $foreign = SourceControl::factory()->github()->create(['project_id' => Project::factory()->create()->id]);
    ($this->detect)(['source_control_id' => $foreign->id])->assertUnprocessable()->assertJsonValidationErrors('source_control_id');

    $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->user->refresh();
    Http::fake();
    ($this->detect)()->assertForbidden();
    Http::assertNothingSent();
});

test('source control providers read repository files through their own api', function (Closure $factory, string $url, string $scheme) {
    $source = $factory();
    if ($source->provider === 'github-app') {
        Cache::put('github_app_token_'.$source->external_identifier, 'token', 60);
    }
    Http::fake(['bitbucket.org/site/oauth2/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]), '*' => Http::response('{"ok":true}')]);

    expect($source->provider()->getFileContents('acme/widget', 'feature/x', 'composer.json'))->toBe('{"ok":true}');

    Http::assertSent(fn (Request $request): bool => $request->url() === $url && str_starts_with($request->header('Authorization')[0] ?? '', $scheme.' '));
})->with([
    'github' => [fn () => SourceControl::factory()->github()->create(), 'https://api.github.com/repos/acme/widget/contents/composer.json?ref=feature%2Fx', 'Bearer'],
    'github app' => [fn () => SourceControl::factory()->githubApp()->create(), 'https://api.github.com/repos/acme/widget/contents/composer.json?ref=feature%2Fx', 'Bearer'],
    'gitlab' => [fn () => SourceControl::factory()->gitlab()->create(), 'https://gitlab.com/api/v4/projects/acme%2Fwidget/repository/files/composer.json/raw?ref=feature%2Fx', 'Bearer'],
    'gitea' => [fn () => SourceControl::factory()->create(['provider' => 'gitea', 'url' => 'https://gitea.example.com/', 'provider_data' => ['token' => 'token']]), 'https://gitea.example.com/api/v1/repos/acme/widget/raw/composer.json?ref=feature%2Fx', 'Bearer'],
    'bitbucket' => [fn () => SourceControl::factory()->bitbucket()->create(), 'https://api.bitbucket.org/2.0/repositories/acme/widget/src/feature%2Fx/composer.json', 'Basic'],
    'bitbucket v2' => [fn () => SourceControl::factory()->create(['provider' => 'bitbucket-v2', 'provider_data' => ['username' => 'user', 'app_password' => 'secret', 'email' => 'a@example.com', 'token' => 'token']]), 'https://api.bitbucket.org/2.0/repositories/acme/widget/src/feature%2Fx/composer.json', 'Bearer'],
]);

test('source control providers treat a missing file as absent and resolve their default branch', function () {
    Http::fake([
        'gitlab.com/api/v4/projects/organization%2Frepository/repository/files/*' => Http::response([], 404),
        'gitlab.com/api/v4/projects/organization%2Frepository' => Http::response(['default_branch' => 'develop']),
        'api.bitbucket.org/*' => Http::response(['mainbranch' => ['name' => 'stable']]),
    ]);
    $gitlab = SourceControl::factory()->gitlab()->create()->provider();

    expect($gitlab->getFileContents('organization/repository', 'main', 'composer.json'))->toBeNull()
        ->and($gitlab->getDefaultBranch('organization/repository'))->toBe('develop')
        ->and(SourceControl::factory()->bitbucket()->create()->provider()->getDefaultBranch('organization/repository'))->toBe('stable');
});

test('creating an application with a server sets everything up and queues the first deploy', function () {
    $this->post(route('applications.store'), [...$this->input, 'database' => true])->assertSessionDoesntHaveErrors();

    $environment = Environment::query()->sole();
    $component = Component::query()->sole();
    expect($environment->deploy_on_ready)->toBeTrue()
        ->and($environment->auto_deploy)->toBeTrue()
        ->and($environment->last_error)->toBeNull()
        ->and($environment->primary_component_id)->toBe($component->id)
        ->and($component->name)->toBe('web')
        ->and($component->user)->toBe('shop')
        ->and($component->isolated_user_id)->not->toBeNull()
        ->and($component->capabilities()->sole()->capability)->toBe('web')
        ->and($environment->variables()->pluck('key')->sort()->values()->all())->toBe(['APP_DEBUG', 'APP_ENV', 'APP_KEY']);
    $this->assertDatabaseHas('databases', ['server_id' => $this->server->id, 'name' => 'shop_production']);
    expect(EnvironmentAttachment::query()->sole()->environment_id)->toBe($environment->id);
    expect(GitHook::query()->where('repository', 'organization/repository')->sole()->hook_id)->toBe('77');
    Queue::assertPushed(InstallComponentJob::class);
    Queue::assertPushed(SyncEnvironmentResourceAccessJob::class);
    Queue::assertNotPushed(StartEnvironmentDeploymentJob::class);
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('the database name is unique on the server', function () {
    $this->server->databases()->create(['name' => 'shop_production', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_general_ci', 'status' => 'ready']);

    $this->post(route('applications.store'), [...$this->input, 'database' => true])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('databases', ['name' => 'shop_production_2']);
});

test('advanced choices are respected', function () {
    $this->post(route('applications.store'), [...$this->input, 'user' => 'shop', 'auto_deploy' => false, 'capabilities' => [
        ['capability' => 'web', 'config' => ['web_directory' => 'www']],
        ['capability' => 'scheduler', 'config' => ['command' => 'php artisan schedule:run']],
    ], 'environment' => [...$this->input['environment'], 'app_key' => $this->appKey]])->assertSessionDoesntHaveErrors();

    $environment = Environment::query()->sole();
    expect($environment->auto_deploy)->toBeFalse()
        ->and(Component::query()->sole()->user)->toBe('shop')
        ->and(Component::query()->sole()->capabilities()->pluck('capability')->sort()->values()->all())->toBe(['scheduler', 'web'])
        ->and($environment->variables()->where('key', 'APP_KEY')->sole()->value)->toBe($this->appKey);
    expect(GitHook::query()->count())->toBe(0);
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/hooks'));
});

test('creating an application without a server keeps the manual flow', function () {
    unset($this->input['server_id']);

    $this->post(route('applications.store'), $this->input)->assertSessionDoesntHaveErrors();

    expect(Environment::query()->sole()->deploy_on_ready)->toBeFalse()
        ->and(Environment::query()->sole()->auto_deploy)->toBeFalse();
    $this->assertDatabaseCount('components', 0);
});

test('a setup that cannot start creates nothing', function (Closure $mutate, string $field) {
    $input = $mutate($this->input, $this->server);

    $this->postJson(route('applications.store'), $input)->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('applications', 0);
    $this->assertDatabaseCount('environments', 0);
    $this->assertDatabaseCount('components', 0);
    $this->assertDatabaseCount('environment_attachments', 0);
    $this->assertDatabaseCount('databases', 0);
})->with([
    'foreign server' => [fn (array $input) => [...$input, 'server_id' => 99999], 'server_id'],
    'zero server id' => [fn (array $input) => [...$input, 'server_id' => 0], 'server_id'],
    'invalid server id' => [fn (array $input) => [...$input, 'server_id' => ['1']], 'server_id'],
    'bad user' => [fn (array $input) => [...$input, 'user' => '../root'], 'user'],
    'ssh user' => [function (array $input, $server) {
        $server->update(['ssh_user' => 'deploy']);

        return [...$input, 'user' => 'deploy'];
    }, 'user'],
    'php version not installed' => [fn (array $input) => [...$input, 'environment' => [...$input['environment'], 'runtime_version' => '5.6']], 'server_id'],
    'unknown capability' => [fn (array $input) => [...$input, 'capabilities' => [['capability' => 'nope']]], 'capabilities.0.capability'],
    'database without firewall' => [function (array $input, $server) {
        $server->firewall()->update(['status' => ServiceStatus::STOPPED]);

        return [...$input, 'database' => true];
    }, 'database'],
    'array app key' => [fn (array $input) => [...$input, 'environment' => [...$input['environment'], 'app_key' => ['x']]], 'environment.app_key'],
    'malformed app key' => [fn (array $input) => [...$input, 'environment' => [...$input['environment'], 'app_key' => 'not-a-key']], 'environment.app_key'],
]);

test('a webhook failure turns auto deploy off with a warning instead of failing creation', function () {
    $this->hookResponse = Http::response(['message' => 'Forbidden'], 403);

    $this->post(route('applications.store'), $this->input)->assertSessionDoesntHaveErrors()->assertRedirect();

    $environment = Environment::query()->sole();
    expect($environment->auto_deploy)->toBeFalse()
        ->and($environment->auto_deploy_failed)->toBeTrue()
        ->and($environment->deploy_on_ready)->toBeTrue();
    expect(GitHook::query()->count())->toBe(0);
    Queue::assertPushed(InstallComponentJob::class);
    $warnings = fn () => app(GetEnvironmentOverview::class)->props($environment->refresh(), null, null)['warnings']();
    expect($warnings()[0])->toContain('webhook');

    $environment->last_error = null;
    $environment->save();
    expect($warnings()[0])->toContain('webhook');

    app(UpdateEnvironment::class)->update($environment, ['name' => $environment->name, 'branch' => $environment->branch, 'runtime_version' => $environment->runtime_version, 'auto_deploy' => false]);
    expect($warnings())->toBe([]);
});

test('a failing hook cleanup does not fail creation', function () {
    $this->hookResponse = Http::response(['message' => 'Forbidden'], 403);
    $other = Application::factory()->create(['project_id' => $this->server->project_id, 'source_control_id' => $this->sourceControl->id, 'repository' => 'organization/repository']);
    Environment::factory()->for($other)->create(['auto_deploy' => true]);
    $hook = new GitHook(['source_control_id' => $this->sourceControl->id, 'secret' => 'secret', 'events' => ['push'], 'actions' => ['deploy']]);
    $hook->repository = 'organization/repository';
    $hook->save();

    $this->post(route('applications.store'), $this->input)->assertSessionDoesntHaveErrors()->assertRedirect();

    expect(Application::query()->where('name', 'shop')->sole()->environments()->sole()->auto_deploy_failed)->toBeTrue();
});

test('a pasted app key is not flashed to the session', function () {
    $this->from(route('applications'))->post(route('applications.store'), [...$this->input, 'server_id' => 99999, 'environment' => [...$this->input['environment'], 'app_key' => $this->appKey]])
        ->assertSessionHasErrors('server_id');

    expect(session()->getOldInput('environment.app_key'))->toBeNull()
        ->and(session()->getOldInput('environment.name'))->toBe('Production');
});

test('providers without repository inspection fall back to manual selection', function () {
    $provider = Mockery::mock(SupportsApplications::class);
    $this->mock(ValidateApplication::class, fn ($mock) => $mock->shouldReceive('provider')->andReturn($provider));

    ($this->detect)(['branch' => 'main'])->assertSuccessful()->assertExactJson(['branch' => 'main', 'runtime' => null, 'versions' => []]);
    expect((new ReflectionClass(SupportsApplications::class))->hasMethod('getFileContents'))->toBeFalse();
});

describe('first deploy', function () {
    beforeEach(function () {
        $this->post(route('applications.store'), $this->input)->assertSessionDoesntHaveErrors();
        $this->environment = Environment::query()->sole();
        $this->component = Component::query()->sole();
        Queue::fake();
    });

    test('waits for the component to be ready', function () {
        expect(app(DeployEnvironment::class)->whenReady($this->environment))->toBeNull();

        $this->component->forceFill(['status' => ComponentStatus::READY])->save();
        $run = app(DeployEnvironment::class)->whenReady($this->environment);

        expect($run)->not->toBeNull()
            ->and($this->environment->refresh()->deploy_on_ready)->toBeFalse()
            ->and(app(DeployEnvironment::class)->whenReady($this->environment))->toBeNull();
        Queue::assertPushed(StartEnvironmentDeploymentJob::class, 1);
    });

    test('waits for attached resources to be ready', function () {
        $this->component->forceFill(['status' => ComponentStatus::READY])->save();
        EnvironmentAttachment::factory()->for($this->environment)->create(['server_id' => $this->server->id, 'status' => EnvironmentAttachmentStatus::PENDING]);

        expect(app(DeployEnvironment::class)->whenReady($this->environment))->toBeNull()
            ->and($this->environment->refresh()->deploy_on_ready)->toBeTrue();
    });

    test('does not run for environments that did not ask for it', function () {
        $this->component->forceFill(['status' => ComponentStatus::READY])->save();
        $this->environment->deploy_on_ready = false;
        $this->environment->save();

        expect(app(DeployEnvironment::class)->whenReady($this->environment))->toBeNull();
        Queue::assertNotPushed(StartEnvironmentDeploymentJob::class);
    });

    test('a manual deploy replaces the queued one', function () {
        $this->component->forceFill(['status' => ComponentStatus::READY])->save();
        app(DeployEnvironment::class)->run($this->environment);

        expect($this->environment->refresh()->deploy_on_ready)->toBeFalse()
            ->and(app(DeployEnvironment::class)->whenReady($this->environment))->toBeNull();
        Queue::assertPushed(StartEnvironmentDeploymentJob::class, 1);
    });

    test('reports a first deploy that can no longer start', function () {
        $this->component->forceFill(['status' => ComponentStatus::READY])->save();
        Application::query()->sole()->forceFill(['source_control_id' => null])->save();

        expect(app(DeployEnvironment::class)->whenReady($this->environment))->toBeNull()
            ->and($this->environment->refresh()->deploy_on_ready)->toBeFalse()
            ->and($this->environment->last_error)->not->toBeEmpty();
        Queue::assertNotPushed(StartEnvironmentDeploymentJob::class);
    });

    test('detaching a failed resource starts it', function () {
        $this->component->forceFill(['status' => ComponentStatus::READY])->save();
        $attachment = EnvironmentAttachment::factory()->for($this->environment)->create(['server_id' => $this->server->id, 'status' => EnvironmentAttachmentStatus::DELETING]);

        (new SyncEnvironmentResourceAccessJob($attachment->id, $this->server->id))->withFakeQueueInteractions()->handle();

        $this->assertDatabaseMissing('environment_attachments', ['id' => $attachment->id]);
        expect($this->environment->refresh()->deploy_on_ready)->toBeFalse();
        Queue::assertPushed(StartEnvironmentDeploymentJob::class, 1);
    });

    test('an unexpected failure is reported without failing the component', function () {
        $this->component->forceFill(['status' => ComponentStatus::READY])->save();
        $this->mock(ValidateEnvironmentResource::class, fn ($mock) => $mock->shouldReceive('ready')->andThrow(new RuntimeException('boom')));

        expect(app(DeployEnvironment::class)->whenReady($this->environment))->toBeNull()
            ->and($this->environment->refresh()->deploy_on_ready)->toBeFalse()
            ->and($this->environment->last_error)->toContain('first deploy could not start')
            ->and($this->component->refresh()->status)->toBe(ComponentStatus::READY);
    });

    test('installing the component starts it', function () {
        (new InstallComponentJob($this->component->id, $this->server->id))->withFakeQueueInteractions()->handle();

        expect($this->component->refresh()->status)->toBe(ComponentStatus::READY)
            ->and($this->environment->refresh()->deploy_on_ready)->toBeFalse();
        Queue::assertPushed(StartEnvironmentDeploymentJob::class, 1);
    });
});

describe('with the real queue', function () {
    beforeEach(function () {
        app()->forgetInstance('queue');
        Queue::clearResolvedInstances();
        config(['queue.default' => 'sync']);
    });

    test('one request carries an application from creation to its first release', function () {
        $this->post(route('applications.store'), [...$this->input, 'database' => true])->assertSessionDoesntHaveErrors();

        $environment = Environment::query()->sole();
        expect(Component::query()->sole()->status)->toBe(ComponentStatus::READY)
            ->and(EnvironmentAttachment::query()->sole()->status)->toBe(EnvironmentAttachmentStatus::READY)
            ->and($environment->deploy_on_ready)->toBeFalse()
            ->and($environment->deployments()->count())->toBe(1);
        expect($environment->deployments()->sole()->status)->toBe(EnvironmentDeploymentStatus::FINISHED)
            ->and($environment->live_run_id)->not->toBeNull();
        expect($environment->deploymentScripts()->where('name', 'build')->sole()->content)->toContain('npm run build --if-present');
    });

    test('a rolled back setup never runs its queued work', function () {
        $this->server->firewall()->update(['status' => ServiceStatus::STOPPED]);

        $this->postJson(route('applications.store'), [...$this->input, 'database' => true])->assertUnprocessable()->assertJsonValidationErrors('database');

        $this->assertDatabaseCount('components', 0);
        expect(SSH::getExecutedCommands())->toBeEmpty();
    });
});

test('php installs restart fpm so the pool runs as the server user', function () {
    expect(view('ssh.services.php.install-php', ['version' => '8.4', 'user' => 'vito', 'useSury' => false])->render())
        ->toContain('s/www-data/vito/g')
        ->toContain('systemctl restart php8.4-fpm')
        ->not->toContain('systemctl start php8.4-fpm');
});

test('server options describe installed services and whether a database can be created', function () {
    $server = collect(app(GetComponentOptions::class)->servers($this->server->project_id))->firstWhere('id', $this->server->id);

    expect($server['services']['php'])->toContain($this->server->php()->version)
        ->and($server['services'])->toHaveKeys(['webserver', 'database', 'firewall'])
        ->and($server['database'])->toBe($this->server->database()->name);

    $this->server->firewall()->update(['status' => ServiceStatus::STOPPED]);
    expect(collect(app(GetComponentOptions::class)->servers($this->server->project_id))->firstWhere('id', $this->server->id)['database'])->toBeNull();
});

test('the database option names the service setup will use', function () {
    $this->server->database()->update(['is_default' => 1, 'status' => ServiceStatus::STOPPED]);
    $this->server->services()->create(['type' => 'database', 'name' => 'postgresql', 'version' => '16', 'status' => ServiceStatus::READY, 'is_default' => 0]);

    expect(collect(app(GetComponentOptions::class)->servers($this->server->project_id))->firstWhere('id', $this->server->id)['database'])->toBeNull();
});

test('turning deploy on push back on keeps the warning until the webhook installs', function () {
    $this->hookResponse = Http::response(['message' => 'Forbidden'], 403);
    $this->post(route('applications.store'), $this->input)->assertSessionDoesntHaveErrors();
    $environment = Environment::query()->sole();
    $input = ['name' => $environment->name, 'branch' => $environment->branch, 'runtime_version' => $environment->runtime_version, 'auto_deploy' => true];

    expect(fn () => app(UpdateEnvironment::class)->update($environment, $input))->toThrow(FailedToDeployGitHook::class);
    expect($environment->refresh()->auto_deploy_failed)->toBeTrue();

    $this->hookResponse = Http::response(['id' => 77], 201);
    app(UpdateEnvironment::class)->update($environment, $input);
    expect($environment->refresh()->auto_deploy_failed)->toBeFalse();
});

test('the applications page offers servers only to members who can create', function () {
    $this->get(route('applications'))->assertInertia(fn ($page) => $page->has('servers', 1)->where('servers.0.id', $this->server->id));

    $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->user->refresh();
    $this->get(route('applications'))->assertInertia(fn ($page) => $page->where('servers', []));
});

test('package manager applications provision node and their manager with editable deployment defaults', function (string $runtime) {
    $this->post(route('applications.store'), [
        ...$this->input,
        'runtime' => $runtime,
        'environment' => ['name' => 'Production', 'branch' => 'main', 'runtime_version' => '22'],
    ])->assertRedirect()->assertSessionDoesntHaveErrors()->assertSessionMissing('error');

    $component = Component::query()->sole();
    $environment = $component->environment;
    $preset = $environment->application->preset();
    $this->assertDatabaseHas('applications', ['id' => $environment->application_id, 'runtime' => $runtime]);
    $this->assertDatabaseHas('environments', ['id' => $environment->id, 'runtime_version' => '22']);
    $this->assertDatabaseHas('deployment_scripts', ['environment_id' => $environment->id, 'name' => 'build', 'content' => $preset->defaultScripts()['build']]);
    expect($component->capabilities()->sole()->config['start_command'])->toBe($runtime.' start');

    (new InstallComponentJob($component->id, $this->server->id))->withFakeQueueInteractions()->handle();

    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'ready', 'installed_runtime_version' => '22']);
    SSH::assertExecutedContains('mise use --path '.escapeshellarg($component->path.'/mise.toml')." 'node@22'");
    SSH::assertExecutedContains('mise use --path '.escapeshellarg($component->path.'/mise.toml').' '.escapeshellarg($runtime.'@'.$preset->additionalTooling()[$runtime]));
    SSH::assertNotExecutedContains('mise use -g');

    $script = base64_decode(str(ComponentShell::wrap($component->refresh(), $runtime.' start'))->between('$(echo ', ' | base64')->toString());
    expect($script)->toContain('/.local/share/mise/shims');

    $this->site->update(['user' => $component->user]);
    foreach (['node', $runtime] as $tool) {
        expect(fn () => app(AssertToolingNotUsedByComponents::class)->assert($this->site, $tool))->toThrow(ValidationException::class);
    }
})->with(['pnpm', 'yarn']);

test('compiled and python application runtimes are detected ahead of frontend assets', function (array $files, string $runtime, array $versions) {
    $this->files = [...$files, 'package.json' => '{}', 'index.html' => '<html>'];

    ($this->detect)()->assertSuccessful()->assertExactJson([
        'branch' => 'trunk', 'runtime' => $runtime, 'versions' => $versions,
    ]);

    $this->files['composer.json'] = '{"require":{"php":"^8.4"}}';
    ($this->detect)()->assertSuccessful()->assertJsonPath('runtime', 'php');
})->with([
    [['go.mod' => "module example.com/app\ngo 1.26"], 'go', ['1.27', '1.26']],
    [['Cargo.toml' => '[package]', 'rust-toolchain' => '1.98.0'], 'rust', ['1.98']],
    [['Cargo.toml' => '[package]', 'rust-toolchain' => "[toolchain]\nchannel = \"1.98.0\""], 'rust', ['1.98']],
    [['Cargo.toml' => '[package]', 'rust-toolchain.toml' => "[toolchain]\nchannel = \"1.98.0\""], 'rust', ['1.98']],
    [['requirements.txt' => '', '.python-version' => '3.13'], 'python', ['3.13']],
    [['pyproject.toml' => "[project]\nrequires-python = \">=3.12,<3.14\""], 'python', ['3.13', '3.12']],
]);

test('compiled and python applications provision isolated tooling and editable deployment defaults', function (string $runtime, string $version, string $start) {
    $this->post(route('applications.store'), [
        ...$this->input,
        'runtime' => $runtime,
        'environment' => ['name' => 'Production', 'branch' => 'main', 'runtime_version' => $version],
    ])->assertRedirect()->assertSessionDoesntHaveErrors()->assertSessionMissing('error');

    $component = Component::query()->sole();
    $environment = $component->environment;
    $preset = $environment->application->preset();
    $this->assertDatabaseHas('applications', ['id' => $environment->application_id, 'runtime' => $runtime]);
    $this->assertDatabaseHas('environments', ['id' => $environment->id, 'runtime_version' => $version]);
    $this->assertDatabaseHas('deployment_scripts', ['environment_id' => $environment->id, 'name' => 'build', 'content' => $preset->defaultScripts()['build']]);
    expect($component->capabilities()->sole()->config['start_command'])->toBe($start);

    (new InstallComponentJob($component->id, $this->server->id))->withFakeQueueInteractions()->handle();

    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'ready', 'installed_runtime_version' => $version]);
    SSH::assertExecutedContains('mise use --path '.escapeshellarg($component->path.'/mise.toml').' '.escapeshellarg($runtime.'@'.$version));
    SSH::assertNotExecutedContains('mise use -g');

    $script = base64_decode(str(ComponentShell::wrap($component->refresh(), $start))->between('$(echo ', ' | base64')->toString());
    expect($script)->toContain('/.local/share/mise/shims');

    $this->site->update(['user' => $component->user]);
    expect(fn () => app(AssertToolingNotUsedByComponents::class)->assert($this->site, $runtime))->toThrow(ValidationException::class);
})->with([
    ['go', '1.27', './app'],
    ['rust', '1.99', './target/release/app'],
    ['python', '3.14', '.venv/bin/python main.py'],
]);

test('laravel components get node on their path and keep it from being uninstalled', function () {
    $this->post(route('applications.store'), $this->input)->assertSessionDoesntHaveErrors();
    $component = Component::query()->sole();

    (new InstallComponentJob($component->id, $this->server->id))->withFakeQueueInteractions()->handle();
    SSH::assertExecutedContains('node@'.(new Laravel)->additionalTooling()['node']);

    $script = base64_decode(str(ComponentShell::wrap($component->refresh(), 'true'))->between('$(echo ', ' | base64')->toString());
    expect($script)->toContain('/.local/share/mise/shims');

    $this->site->update(['user' => $component->user]);
    expect(fn () => app(AssertToolingNotUsedByComponents::class)->assert($this->site, 'node'))->toThrow(ValidationException::class);
});
