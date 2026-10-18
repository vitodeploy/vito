<?php

use App\Actions\HostedDomain\AssertHostAvailable;
use App\Actions\IsolatedUser\EnsureIsolatedUser;
use App\Actions\PHP\EnsureFpmPool;
use App\Actions\SourceControl\SetupDeployKey;
use App\DTOs\GitCloneDTO;
use App\DTOs\ToolingTargetDTO;
use App\Enums\HostedDomainType;
use App\Events\SocketEvent;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Facades\SSH;
use App\Helpers\SiteShellEnvironment;
use App\Models\HostedDomain;
use App\Models\IsolatedUser;
use App\Models\Server;
use App\Models\Site;
use App\Models\SourceControl;
use App\SSH\OS\Git;
use App\Support\Testing\SSHFake;
use App\Tooling\ComposerTooling;
use App\Tooling\NodeTooling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake([SocketEvent::class]);
    Http::preventStrayRequests();
});

test('deploy key setup generates and registers a public key', function () {
    SSH::fake('ssh-rsa test-public-key');
    Http::fake(['api.github.com/repos/owner/repo/keys' => Http::response(['id' => 123], 201)]);

    $key = app(SetupDeployKey::class)->setup(
        $this->server, 'runtime', 'runtime_key', $this->site->sourceControl, 'owner/repo', 'Runtime',
        siteId: $this->site->id,
    );

    expect($key)->toBe(['public_key' => 'ssh-rsa test-public-key', 'deploy_key_id' => '123']);
    SSH::assertExecutedContains('ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/runtime_key');
    SSH::assertExecutedContains('cat ~/.ssh/runtime_key.pub');
    Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/owner/repo/keys'
        && $request['title'] === 'Runtime' && $request['key'] === $key['public_key']);
    $this->assertDatabaseHas('server_logs', ['site_id' => $this->site->id, 'type' => 'generate-ssh-key']);
});

test('deploy key setup reuses a supplied public key without generating another', function () {
    SSH::fake();
    Http::fake(['api.github.com/repos/owner/repo/keys' => Http::response(['id' => 123], 201)]);

    $key = app(SetupDeployKey::class)->setup(
        $this->server, 'runtime', 'shared_key', $this->site->sourceControl, 'owner/repo', 'Runtime',
        publicKey: 'ssh-rsa shared-public-key',
    );

    expect($key['public_key'])->toBe('ssh-rsa shared-public-key');
    expect(SSH::getExecutedCommands())->toBe([]);
    Http::assertSent(fn ($request) => $request['key'] === 'ssh-rsa shared-public-key');
});

test('deploy key setup skips github app authentication', function () {
    SSH::fake();
    Http::fake();

    $sourceControl = SourceControl::factory()->githubApp()->create();
    $key = app(SetupDeployKey::class)->setup($this->server, 'runtime', 'unused', $sourceControl, 'owner/repo', 'Runtime');

    expect($key)->toBe(['public_key' => null, 'deploy_key_id' => null]);
    expect(SSH::getExecutedCommands())->toBe([]);
    Http::assertNothingSent();
});

test('site deploy key retries preserve generated keys and skip completed registration', function (bool $isolated) {
    SSH::fake('ssh-rsa retry-public-key');
    $responses = Http::sequence()->push([], 403)->push(['id' => 123], 201);
    Http::fake(fn ($request) => $request->method() === 'POST' ? $responses($request) : Http::response([]));
    $site = $isolated ? Site::factory()->create([
        'server_id' => $this->server->id,
        'source_control_id' => $this->site->source_control_id,
        'repository' => 'owner/repo',
        'user' => 'shareduser',
        'ssh_key' => null,
    ]) : $this->site;
    $site->update(['ssh_key' => null, 'type_data' => []]);
    $type = $site->type();
    $method = new ReflectionMethod($type, 'deployKey');

    expect(fn () => $method->invoke($type))->toThrow(FailedToDeployGitKey::class);
    expect($site->fresh()->ssh_key)->toBe('ssh-rsa retry-public-key');
    if ($isolated) {
        expect($site->isolatedUser->fresh()->ssh_key)->toBe('ssh-rsa retry-public-key');
    }

    SSH::fake();
    $method->invoke($type);
    $method->invoke($type);

    expect(SSH::getExecutedCommands())->toBe([]);
    Http::assertSentCount(3);
    $this->assertDatabaseHas('sites', ['id' => $site->id, 'type_data->deploy_key_id' => '123']);
})->with([false, true]);

test('isolated user setup is repeatable and releases the shared lock', function () {
    SSH::fake();
    $action = app(EnsureIsolatedUser::class);

    $action->ensure($this->server, 'runtime', $this->site->id);
    $action->ensure($this->server, 'runtime');

    SSH::assertExecutedContains('if id -u runtime');
    SSH::assertExecutedContains('User runtime already exists');
    expect(SSH::getExecutedCommands())->toHaveCount(2);
    $this->assertDatabaseHas('server_logs', ['site_id' => $this->site->id, 'type' => 'create-isolated-user']);
    expect($this->server->isolatedUserLock('runtime')->get(fn () => true))->toBeTrue();
});

test('isolated user failures bubble up and release the lock', function () {
    SSH::fake()->execWillFail();

    expect(fn () => app(EnsureIsolatedUser::class)->ensure($this->server, 'runtime'))->toThrow(SSHCommandError::class);
    expect($this->server->isolatedUserLock('runtime')->get(fn () => true))->toBeTrue();
});

test('fpm pool setup creates only a missing pool', function () {
    $ssh = Mockery::mock(SSHFake::class, [null])->makePartial();
    SSH::swap($ssh);
    $checks = 0;
    $ssh->shouldReceive('exec')
        ->withArgs(fn ($command) => str_contains((string) $command, 'test -f /etc/php/8.2/fpm/pool.d/runtime.conf'))
        ->twice()
        ->andReturnUsing(function () use (&$checks): string {
            if ($checks++ === 0) {
                throw new SSHCommandError;
            }

            return '';
        });

    app(EnsureFpmPool::class)->ensure($this->server, 'runtime', '8.2');
    app(EnsureFpmPool::class)->ensure($this->server, 'runtime', '8.2');

    expect($ssh->getUploadedContent())->toContain('[runtime]');
    $restarts = array_filter(SSH::getExecutedCommands(), fn ($command) => str_contains($command, 'restart php8.2-fpm'));
    expect($restarts)->toHaveCount(1);
    expect($this->server->isolatedUserLock('runtime')->get(fn () => true))->toBeTrue();
});

test('fpm pool setup does not treat connection failures as missing pools', function () {
    $ssh = Mockery::mock(SSHFake::class, [null])->makePartial();
    SSH::swap($ssh);
    $ssh->shouldReceive('exec')->once()->andThrow(new SSHConnectionError('Connection failed'));

    expect(fn () => app(EnsureFpmPool::class)->ensure($this->server, 'runtime', '8.2'))->toThrow(SSHConnectionError::class);
    expect($ssh->getUploadedContent())->toBe('');
    expect($this->server->isolatedUserLock('runtime')->get(fn () => true))->toBeTrue();
});

test('fpm pool setup requires an exact php service without changing the default', function (string $version) {
    SSH::fake();
    $this->server->services()->update(['is_default' => false]);

    expect(fn () => app(EnsureFpmPool::class)->ensure($this->server, 'runtime', $version))->toThrow(RuntimeException::class, 'PHP service not found');
    expect(SSH::getExecutedCommands())->toBe([]);
    expect($this->server->services()->where('is_default', true)->exists())->toBeFalse();
})->with(['0.0', '', '0']);

test('repository clone supports token and ssh authentication with optional commit pinning', function (bool $usesToken, ?string $commit) {
    SSH::fake();
    $path = "/home/runtime/release's path";
    $url = $usesToken ? 'https://github.com/owner/repo.git' : 'git@github.com-runtime_key:owner/repo.git';
    $target = new GitCloneDTO(
        server: $this->server,
        user: 'runtime',
        repoUrl: $url,
        branch: 'main',
        path: $path,
        keyName: 'runtime_key',
        port: 2222,
        usesToken: $usesToken,
        variables: $usesToken ? ['GIT_HTTP_TOKEN' => 'test-token'] : [],
        commit: $commit,
        siteId: $this->site->id,
    );

    app(Git::class)->cloneRepository($target);

    $commands = SSH::getExecutedCommands();
    expect($commands)->toHaveCount($commit === null ? 1 : 2);
    expect($commands[0])->toContain('clone -b '.escapeshellarg('main').' '.escapeshellarg($url).' '.escapeshellarg($path));
    expect($commands[0])->not->toContain('--depth', 'test-token');
    if ($usesToken) {
        expect($commands[0])->toContain('$GIT_HTTP_TOKEN')->not->toContain('ssh-keyscan');
    } else {
        expect($commands[0])->toContain('Port 2222', 'IdentityFile=~/.ssh/runtime_key');
    }
    if ($commit !== null) {
        expect($commands[1])->toContain('cd '.escapeshellarg($path), 'git checkout -f '.escapeshellarg($commit));
        $this->assertDatabaseHas('server_logs', ['site_id' => $this->site->id, 'type' => 'checkout-commit']);
    }
})->with([false, true])->with([null, str_repeat('a', 40)]);

test('source control clone variables expose only github app authentication', function () {
    Http::fake();
    $sourceControl = SourceControl::factory()->githubApp()->create(['external_identifier' => '12345']);
    Cache::put('github_app_token_12345', 'test-token', 60);

    expect($sourceControl->cloneVariables())->toBe(['GIT_HTTP_TOKEN' => 'test-token']);
    expect($this->site->sourceControl->cloneVariables())->toBe([]);
    Http::assertNothingSent();
});

test('tooling can install and collect paths without a site', function () {
    SSH::fake();
    $user = IsolatedUser::factory()->create(['server_id' => $this->server->id, 'username' => 'runtime']);
    $user->setToolingVersion('node', '22');
    $user->setToolingVersion('composer', '2');
    $target = new ToolingTargetDTO($this->server, 'runtime', $user, '8.2');

    new NodeTooling()->install($target, '22');
    new ComposerTooling()->install($target, '2');

    SSH::assertExecutedContains('mise use -g node@22');
    SSH::assertExecutedContains('php8.2 composer-setup.php --2');
    expect(SiteShellEnvironment::collect($target)['PATH'])->toContain('/home/runtime/.local/share/mise/shims', '/home/runtime/.local/vito/bin');

    new NodeTooling()->uninstall($target);
    new ComposerTooling()->uninstall($target);
    SSH::assertExecutedContains('mise uninstall node --all');
    SSH::assertExecutedContains('rm -f');
});

test('host availability rejects site domains and hosted aliases using the supplied field', function (bool $alias) {
    $host = $this->site->domain;
    if ($alias) {
        $host = HostedDomain::factory()->create(['site_id' => $this->site->id, 'domain' => 'alias.example.com'])->domain;
    }

    try {
        app(AssertHostAvailable::class)->assert($this->server, $host, 'aliases.0');
        $this->fail('Expected a host collision.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('aliases.0');
    }
})->with([false, true]);

test('host availability ignores only the requested hosted domain', function (HostedDomainType $type) {
    $host = $type === HostedDomainType::PRIMARY ? $this->site->domain : 'alias.example.com';
    $domain = HostedDomain::factory()->create(['site_id' => $this->site->id, 'domain' => $host, 'type' => $type]);
    $action = app(AssertHostAvailable::class);

    $action->assert($this->server, $host, 'domain', $domain);
    Site::factory()->create(['server_id' => $this->server->id, 'domain' => $host]);

    expect(fn () => $action->assert($this->server, $host, 'domain', $domain))->toThrow(ValidationException::class);
})->with([HostedDomainType::PRIMARY, HostedDomainType::ALIAS]);

test('host availability is scoped to the server', function () {
    HostedDomain::factory()->create(['site_id' => $this->site->id, 'domain' => 'alias.example.com']);
    $otherServer = Server::factory()->create();

    expect(fn () => app(AssertHostAvailable::class)->assert($otherServer, $this->site->domain, 'domain'))->not->toThrow(ValidationException::class);
    expect(fn () => app(AssertHostAvailable::class)->assert($otherServer, 'alias.example.com', 'domain'))->not->toThrow(ValidationException::class);
});

test('release template accepts paths without a site', function () {
    $script = view('ssh.modern-deployment.release', [
        'basePath' => '/home/runtime/app',
        'releasePath' => '/home/runtime/app/releases/123',
    ])->render();

    expect($script)->toContain('ln -sfn /home/runtime/app/releases/123 /home/runtime/app/current', 'Version 123 activated!');
});
