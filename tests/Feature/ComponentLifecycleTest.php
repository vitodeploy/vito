<?php

use App\Actions\Application\DeleteApplication;
use App\Actions\Application\UpdateApplication;
use App\Actions\Component\BroadcastComponentUpdate;
use App\Actions\Component\CreateComponent;
use App\Actions\Component\DeleteComponent;
use App\Actions\Component\InstallComponent;
use App\Actions\Component\PurgeServerComponents;
use App\Actions\Component\RetryComponentInstall;
use App\Actions\Component\SyncComponentDeployKeys;
use App\Actions\Environment\DeleteEnvironment;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Actions\Site\Tooling\UninstallSiteTooling;
use App\Actions\SourceControl\SetupDeployKey;
use App\DTOs\ToolingTargetDTO;
use App\Enums\ComponentStatus;
use App\Events\SocketEvent;
use App\Exceptions\FailedToDeployGitKey;
use App\Facades\SSH;
use App\Jobs\Application\DeleteApplicationJob;
use App\Jobs\Component\DeleteComponentJob;
use App\Jobs\Component\DeletePurgedComponentDeployKeyJob;
use App\Jobs\Component\InstallComponentJob;
use App\Jobs\Component\SyncComponentDeployKeysJob;
use App\Jobs\Environment\DeleteEnvironmentJob;
use App\Jobs\Site\Tooling\UninstallSiteToolingJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\IsolatedUser;
use App\Models\Project;
use App\Models\Server;
use App\Models\SourceControl;
use App\SSH\Mise\Mise;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake('ssh-rsa component-public-key');
    Http::preventStrayRequests();
    Http::fake(fn ($request) => Http::response($request->method() === 'POST' ? ['id' => 123] : [], $request->method() === 'POST' ? 201 : 204));
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'source_control_id' => $this->site->source_control_id]);
    $this->environment = Environment::factory()->for($this->application)->create(['runtime_version' => '22']);
    $this->component = app(CreateComponent::class)->create($this->environment, [
        'name' => 'Runtime', 'server_id' => $this->server->id, 'user' => 'runtime', 'type' => 'app',
    ]);
});

test('component key registration rejects a source moved to another project even with a cached relation', function () {
    $this->component->load('environment.application.sourceControl');
    $this->application->sourceControl->update(['project_id' => Project::factory()->create()->id]);
    expect(fn () => app(SyncComponentDeployKeys::class)->sync($this->component))->toThrow(ValidationException::class);
    Http::assertNothingSent();
    expect(SSH::getExecutedCommands())->toBeEmpty();
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'deploy_key_id' => null]);
});

test('component key cleanup retains custody after source reassignment', function () {
    app(InstallComponent::class)->install($this->component->id);
    $this->application->sourceControl->update(['project_id' => Project::factory()->create()->id]);
    app(SyncComponentDeployKeys::class)->remove($this->component->fresh());
    Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/keys/123'));
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'deploy_key_id' => null, 'deploy_key_source_control_id' => null]);
});

test('component install provisions directories tooling and its own deploy key idempotently', function () {
    app(InstallComponent::class)->install($this->component->id);
    app(InstallComponent::class)->install($this->component->id);

    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'status' => 'ready', 'ssh_key' => 'ssh-rsa component-public-key', 'deploy_key_id' => '123', 'deploy_key_repository' => $this->application->repository]);
    SSH::assertExecutedContains('if id -u runtime');
    SSH::assertExecutedContains('mkdir -p -- '.escapeshellarg($this->component->releasesPath()).' '.escapeshellarg($this->component->sharedPath()));
    SSH::assertExecutedContains('mise use --path '.escapeshellarg($this->component->path.'/mise.toml')." 'node@22'");
    SSH::assertExecutedContains('mise trust '.escapeshellarg($this->component->path.'/mise.toml'));
    SSH::assertExecutedContains('~/.ssh/component_'.$this->component->id);
    SSH::assertNotExecutedContains('mise use -g');
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['title'] === 'component_'.$this->component->id);
    Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'component.updated');
});

test('shared linux users support independent component runtime versions without changing site globals', function () {
    $environment = Environment::factory()->for($this->application)->create(['runtime_version' => '24']);
    $other = app(CreateComponent::class)->create($environment, ['name' => 'Other', 'server_id' => $this->server->id, 'user' => 'runtime', 'type' => 'app']);
    app(InstallComponent::class)->install($this->component->id);
    app(InstallComponent::class)->install($other->id);
    expect($other->isolated_user_id)->toBe($this->component->isolated_user_id);
    SSH::assertExecutedContains('mise use --path '.escapeshellarg($other->path.'/mise.toml')." 'node@24'");
    SSH::assertNotExecutedContains('mise use -g');
    expect($this->component->isolatedUser->fresh()->installed_tooling)->toBeNull();
    (new Mise($this->server))->installRuntime(ToolingTargetDTO::forSite($this->site), 'node', '22');
    SSH::assertExecutedContains('mise use -g node@22');
});

test('github app components do not create ssh or provider keys', function () {
    $this->application->update(['source_control_id' => SourceControl::factory()->githubApp()->create()->id]);
    app(InstallComponent::class)->install($this->component->id);
    Http::assertNothingSent();
    SSH::assertNotExecutedContains('ssh-keygen');
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'status' => 'ready', 'deploy_key_id' => null, 'ssh_key' => null]);
});

test('component failed jobs store only safe errors and installation can be retried', function () {
    (new InstallComponentJob($this->component->id, $this->server->id))->failed(new Exception('SECRET=never-expose'));
    expect($this->component->fresh()->last_error)->not->toContain('SECRET');
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'status' => 'installation_failed']);
    Event::fake([SocketEvent::class]);
    app(RetryComponentInstall::class)->retry($this->component);
    Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'component.updated'
        && $event->data->data['status'] === 'pending' && $event->data->data['last_error'] === null);
    (new InstallComponentJob($this->component->id, $this->server->id))->handle();
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'status' => 'ready', 'last_error' => null]);
    expect(fn () => app(RetryComponentInstall::class)->retry($this->component))->toThrow(ValidationException::class);
});

test('component teardown preserves shared isolated users and deletes unshared users', function (bool $shared) {
    Event::listen('eloquent.deleting: '.Component::class, function (Component $component): void {
        $lock = $component->server->isolatedUserLock($component->user);
        $acquired = $lock->get();
        if ($acquired) {
            $lock->release();
        }
        expect($acquired)->toBeFalse();
    });
    if ($shared) {
        $this->site->update(['user' => 'runtime', 'isolated_user_id' => $this->component->isolated_user_id]);
    }
    app(InstallComponent::class)->install($this->component->id);
    Event::fake([SocketEvent::class]);
    app(DeleteComponent::class)->delete($this->component, []);
    Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'component.updated'
        && $event->data->data['status'] === 'deleting');
    (new DeleteComponentJob($this->component->id, $this->server->id))->handle();
    $this->assertDatabaseMissing('components', ['id' => $this->component->id]);
    expect(IsolatedUser::query()->whereKey($this->component->isolated_user_id)->exists())->toBe($shared);
    SSH::assertExecutedContains('rm -rf -- '.escapeshellarg($this->component->path));
    SSH::assertExecutedContains("awk -v suffix='-component_{$this->component->id}'");
    SSH::assertNotExecutedContains('mise uninstall');
    if ($shared) {
        SSH::assertNotExecutedContains('userdel');
    } else {
        SSH::assertExecutedContains('userdel -r -f "runtime"');
    }
    Http::assertSent(fn ($request) => $request->method() === 'DELETE');
})->with([false, true]);

test('deleting the primary accepts an explicit replacement or leaves it unset', function (bool $replace) {
    $other = Component::factory()->for($this->environment)->for($this->server)->create(['user' => 'another']);
    app(DeleteComponent::class)->delete($this->component, ['primary_component_id' => $replace ? $other->id : null]);
    expect($this->environment->fresh()->primary_component_id)->toBe($replace ? $other->id : null);
    (new DeleteComponentJob($this->component->id, $this->server->id))->failed(new Exception('SECRET'));
    expect($this->component->fresh()->last_error)->not->toContain('SECRET');
    app(DeleteComponent::class)->delete($this->component, []);
    (new DeleteComponentJob($this->component->id, $this->server->id))->handle();
    $this->assertDatabaseMissing('components', ['id' => $this->component->id]);
})->with([false, true]);

test('environment teardown chains components before row deletion and can retry failure', function () {
    Bus::fake();
    app(DeleteEnvironment::class)->delete($this->environment, ['name' => $this->environment->name]);
    Bus::assertChained([new DeleteComponentJob($this->component->id, $this->server->id), new DeleteEnvironmentJob($this->environment->id)]);
    app(DeleteEnvironment::class)->failed($this->environment->id);
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'status' => 'deletion_failed']);
    app(DeleteEnvironment::class)->delete($this->environment, ['name' => $this->environment->name]);
    (new DeleteComponentJob($this->component->id, $this->server->id))->handle();
    (new DeleteEnvironmentJob($this->environment->id))->handle();
    $this->assertDatabaseMissing('environments', ['id' => $this->environment->id]);
    $this->assertDatabaseHas('applications', ['id' => $this->application->id]);
});

test('application teardown chains environment cleanup and retains failed parents for retry', function () {
    Bus::fake();
    app(DeleteApplication::class)->delete($this->application, ['name' => $this->application->name]);
    Bus::assertChained([new DeleteComponentJob($this->component->id, $this->server->id), new DeleteEnvironmentJob($this->environment->id), new DeleteApplicationJob($this->application->id)]);
    (new DeleteApplicationJob($this->application->id))->failed(new Exception('SECRET'));
    $this->assertDatabaseHas('applications', ['id' => $this->application->id, 'status' => 'deletion_failed']);
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'status' => 'deletion_failed']);
    app(DeleteApplication::class)->delete($this->application, ['name' => $this->application->name]);
    (new DeleteComponentJob($this->component->id, $this->server->id))->handle();
    (new DeleteEnvironmentJob($this->environment->id))->handle();
    (new DeleteApplicationJob($this->application->id))->handle();
    $this->assertDatabaseMissing('applications', ['id' => $this->application->id]);
});

test('repository changes remove keys from their original source and register the current repository', function () {
    $this->site->sourceControl->update(['user_id' => $this->user->id, 'project_id' => $this->server->project_id]);
    app(InstallComponent::class)->install($this->component->id);
    app(UpdateApplication::class)->update($this->application, ['name' => $this->application->name, 'source_control_id' => $this->site->source_control_id, 'repository' => 'other/repo'], $this->user);
    Queue::assertPushed(SyncComponentDeployKeysJob::class);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'status' => ComponentStatus::PENDING]);
    $this->environment->primary_component_id = $this->component->id;
    $this->environment->save();
    expect(fn () => app(DeployEnvironment::class)->run($this->environment))->toThrow(ValidationException::class);
    (new SyncComponentDeployKeysJob($this->component->id, $this->server->id))->handle();
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'status' => ComponentStatus::READY, 'deploy_key_repository' => 'other/repo', 'needs_redeploy' => true]);
    Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_contains($request->url(), '/organization/repository/keys/123'));
    Http::assertSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/other/repo/keys'));
});

test('changing to github app removes old provider and local keys', function () {
    app(InstallComponent::class)->install($this->component->id);
    $this->application->update(['source_control_id' => SourceControl::factory()->githubApp()->create()->id]);
    app(SyncComponentDeployKeys::class)->handle($this->component->id);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'ssh_key' => null, 'deploy_key_id' => null]);
    SSH::assertExecutedContains('rm -f -- "$HOME/.ssh/component_'.$this->component->id.'"');
    SSH::assertExecutedContains('flock -w 60 9 || exit 1');
    SSH::assertExecutedContains(') 9>"$HOME/.ssh/config.vito.lock"');
});

test('purging servers queues provider cleanup without remote teardown and records safe failures', function () {
    app(InstallComponent::class)->install($this->component->id);
    SSH::fake();
    $departure = app(PurgeServerComponents::class)->purge($this->server);
    app(PurgeServerComponents::class)->handle($departure);
    Queue::assertPushed(DeletePurgedComponentDeployKeyJob::class);
    $job = new DeletePurgedComponentDeployKeyJob($this->component->id, $this->server->id, $this->environment->id, $this->application->source_control_id, $this->application->repository, '123');
    $job->handle();
    $job->failed(new Exception('SECRET'));
    expect($this->environment->fresh()->last_error)->toContain('deploy key')->not->toContain('SECRET');
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('mise rejects mismatched target servers before any remote command', function (bool $install) {
    SSH::fake();
    $target = new ToolingTargetDTO(Server::factory()->create(), 'runtime');
    $mise = new Mise($this->server);
    expect(fn () => $install ? $mise->installRuntime($target, 'node', '22') : $mise->uninstallRuntime($target, 'node'))
        ->toThrow(InvalidArgumentException::class);
    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with([false, true]);

test('queued site tooling removal rechecks components attached after enqueue', function () {
    $this->site->update(['user' => 'runtime', 'isolated_user_id' => $this->component->isolated_user_id]);
    $this->component->isolatedUser->setToolingVersion('node', '22');
    $this->component->delete();
    app(UninstallSiteTooling::class)->uninstall($this->site, 'node');
    Queue::assertPushed(UninstallSiteToolingJob::class);
    app(CreateComponent::class)->create($this->environment, [
        'name' => 'Attached later', 'server_id' => $this->server->id, 'user' => 'runtime', 'type' => 'app',
    ]);
    $job = new UninstallSiteToolingJob($this->site, 'node');
    SSH::fake();
    $job->withFakeQueueInteractions()->handle();
    $job->assertFailedWith(ValidationException::class);
    SSH::assertNotExecutedContains('mise uninstall');
});

test('failed key registration retains the public key and completed removal for retry', function () {
    app(InstallComponent::class)->install($this->component->id);
    $this->application->update(['repository' => 'other/repository']);
    $this->mock(SetupDeployKey::class, function ($mock) {
        $mock->shouldReceive('setup')->once()->andThrow(new RuntimeException('Provider unavailable'));
    });

    expect(fn () => app(SyncComponentDeployKeys::class)->handle($this->component->id))->toThrow(RuntimeException::class);
    $this->assertDatabaseHas('components', [
        'id' => $this->component->id, 'ssh_key' => 'ssh-rsa component-public-key',
        'deploy_key_id' => null, 'deploy_key_source_control_id' => $this->application->source_control_id, 'deploy_key_repository' => 'other/repository',
    ]);
    app()->forgetInstance(SetupDeployKey::class);
    app(SyncComponentDeployKeys::class)->handle($this->component->id);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'deploy_key_repository' => 'other/repository']);
});

test('failed initial key registration persists its generated public key', function () {
    $this->mock(SetupDeployKey::class, function ($mock) {
        $mock->shouldReceive('generateKey')->once()->andReturn('ssh-rsa persisted-key');
        $mock->shouldReceive('setup')->once()->andThrow(new RuntimeException('Provider unavailable'));
    });

    expect(fn () => app(InstallComponent::class)->install($this->component->id))->toThrow(RuntimeException::class);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'ssh_key' => 'ssh-rsa persisted-key', 'deploy_key_id' => null]);
});

test('pending deploy key ownership survives lost responses and is reconciled before cleanup', function (string $operation) {
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(function ($request) {
        $this->assertDatabaseHas('components', [
            'id' => $this->component->id, 'deploy_key_source_control_id' => $this->application->source_control_id,
            'deploy_key_repository' => $this->application->repository, 'ssh_key' => 'ssh-rsa component-public-key',
        ]);
        if ($request->method() === 'POST') {
            throw new ConnectionException('Lost response');
        }

        return Http::response([], 503);
    });
    expect(fn () => app(InstallComponent::class)->install($this->component->id))->toThrow(FailedToDeployGitKey::class);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'deploy_key_id' => null, 'deploy_key_repository' => $this->application->repository]);

    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(fn ($request) => match ($request->method()) {
        'GET' => Http::response([['id' => 321, 'key' => 'ssh-rsa component-public-key']]),
        'POST' => Http::response(['id' => 456], 201),
        default => Http::response([], 204),
    });
    if ($operation === 'repository') {
        $this->application->update(['repository' => 'new/repository']);
        app(InstallComponent::class)->install($this->component->id);
        $this->assertDatabaseHas('components', ['id' => $this->component->id, 'deploy_key_id' => '456', 'deploy_key_repository' => 'new/repository']);
    } elseif ($operation === 'purge') {
        $purge = app(PurgeServerComponents::class);
        $departure = $purge->purge($this->server);
        expect($departure['keys'])->toHaveCount(1);
        $purge->handle($departure);
        Queue::assertPushed(DeletePurgedComponentDeployKeyJob::class, function ($job): bool {
            $job->handle();

            return true;
        });
    } else {
        app(DeleteComponent::class)->delete($this->component, []);
        app(DeleteComponent::class)->handle($this->component->id);
        $this->assertDatabaseMissing('components', ['id' => $this->component->id]);
    }
    Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_contains($request->url(), '/organization/repository/keys/321'));
})->with(['repository', 'delete', 'purge']);

test('parent teardown chains propagate real job failures and broadcast retry state', function (bool $application) {
    Queue::fake()->except([DeleteComponentJob::class, DeleteEnvironmentJob::class, DeleteApplicationJob::class]);
    SSH::fake()->execWillFail();
    $parent = $application ? $this->application : $this->environment;
    $action = $application ? DeleteApplication::class : DeleteEnvironment::class;
    app($action)->delete($parent, ['name' => $parent->name]);

    $this->assertDatabaseHas($parent->getTable(), ['id' => $parent->id, 'status' => 'deletion_failed']);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'status' => 'deletion_failed']);
    Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === ($application ? 'application.updated' : 'environment.updated') && $event->data->data['status'] === 'deletion_failed');
})->with([false, true]);

test('component deletion events carry current parent teardown context', function () {
    Bus::fake();
    app(DeleteApplication::class)->delete($this->application, ['name' => $this->application->name]);
    app(DeleteComponent::class)->handle($this->component->id);
    app(DeleteEnvironment::class)->handle($this->environment->id);
    app(DeleteApplication::class)->handle($this->application->id);

    Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'component.deleted' && $event->data->data['application_status'] === 'deleting' && $event->data->data['environment_status'] === 'deleting');
    Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'environment.deleted');
    Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'application.deleted');
});

test('component deletion refreshes parent teardown context after remote cleanup', function () {
    app(DeleteComponent::class)->delete($this->component, []);
    Event::listen('eloquent.deleting: '.Component::class, function (): void {
        $this->application->forceFill(['status' => 'deleting'])->save();
        $this->environment->forceFill(['status' => 'deleting'])->save();
    });

    app(DeleteComponent::class)->handle($this->component->id);

    Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'component.deleted' && $event->data->data['application_status'] === 'deleting' && $event->data->data['environment_status'] === 'deleting');
});

test('component deletion broadcasts skip parents already removed', function () {
    $this->component->load('environment.application');
    $this->component->delete();
    $this->environment->delete();

    app(BroadcastComponentUpdate::class)->deleted($this->component);

    Event::assertNotDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'component.deleted');
});

test('server purge cleanup waits for in flight deploy key registration', function () {
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(function ($request) {
        if ($request->method() === 'POST') {
            $this->server->deleteFromProvider = false;
            $this->server->delete();
            Queue::assertPushed(DeletePurgedComponentDeployKeyJob::class, function ($job): bool {
                $job->withFakeQueueInteractions()->handle();
                $job->assertReleased(30);

                return true;
            });

            return Http::response(['id' => 456], 201);
        }

        return $request->method() === 'GET'
            ? Http::response([['id' => 456, 'key' => 'ssh-rsa component-public-key']])
            : Http::response([], 204);
    });

    (new InstallComponentJob($this->component->id, $this->server->id))->withFakeQueueInteractions()->handle();
    $this->assertDatabaseMissing('servers', ['id' => $this->server->id]);
    Queue::assertPushed(DeletePurgedComponentDeployKeyJob::class, function ($job): bool {
        $job->handle();

        return true;
    });
    Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/456'));
});

test('components removed before pending ownership is persisted never register a provider key', function () {
    $this->mock(SetupDeployKey::class, function ($mock) {
        $mock->shouldReceive('generateKey')->once()->andReturnUsing(function (): string {
            $this->component->delete();

            return 'ssh-rsa component-public-key';
        });
        $mock->shouldNotReceive('setup');
    });

    expect(fn () => app(InstallComponent::class)->install($this->component->id))->toThrow(ModelNotFoundException::class);
    Http::assertNothingSent();
});

test('pending key retries recover their recorded repository without another registration', function () {
    $this->component->forceFill([
        'ssh_key' => 'ssh-rsa component-public-key', 'deploy_key_source_control_id' => $this->application->source_control_id,
        'deploy_key_repository' => $this->application->repository,
    ])->save();
    Http::swap(new Factory);
    Http::fake(['*' => Http::response([['id' => 789, 'key' => 'ssh-rsa component-public-key']])]);

    app(InstallComponent::class)->install($this->component->id);

    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'status' => 'ready', 'deploy_key_id' => '789']);
    Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    SSH::assertNotExecutedContains('ssh-keygen');
});

test('inconclusive pending key cleanup preserves ownership and local resources', function () {
    $this->component->forceFill([
        'ssh_key' => 'ssh-rsa component-public-key', 'deploy_key_source_control_id' => $this->application->source_control_id,
        'deploy_key_repository' => $this->application->repository,
    ])->save();
    Http::swap(new Factory);
    Http::fake(['*' => Http::response('invalid JSON')]);
    app(DeleteComponent::class)->delete($this->component, []);

    expect(fn () => app(DeleteComponent::class)->handle($this->component->id))->toThrow(FailedToDeployGitKey::class);
    $this->assertDatabaseHas('components', ['id' => $this->component->id, 'deploy_key_repository' => $this->application->repository, 'ssh_key' => 'ssh-rsa component-public-key']);
    expect(SSH::getExecutedCommands())->toBe([]);
});

test('parent teardown runs the complete job chain before removing parents', function (bool $application) {
    Queue::fake()->except([DeleteComponentJob::class, DeleteEnvironmentJob::class, DeleteApplicationJob::class]);
    $parent = $application ? $this->application : $this->environment;
    app($application ? DeleteApplication::class : DeleteEnvironment::class)->delete($parent, ['name' => $parent->name]);

    $this->assertDatabaseMissing('components', ['id' => $this->component->id]);
    $this->assertDatabaseMissing('environments', ['id' => $this->environment->id]);
    expect(Application::query()->whereKey($this->application->id)->exists())->toBe(! $application);
    Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === ($application ? 'application.deleted' : 'environment.deleted'));
})->with([false, true]);
