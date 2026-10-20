<?php

use App\Actions\Application\DeleteApplication;
use App\Actions\Application\UpdateApplication;
use App\Actions\Environment\DeleteEnvironment;
use App\Actions\Environment\UpdateEnvironment;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Actions\GitHook\SyncRepositoryGitHook;
use App\Actions\GithubApp\HandlePushWebhook;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Events\SocketEvent;
use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDestroyGitHook;
use App\Facades\SSH;
use App\Jobs\Application\DeleteApplicationJob;
use App\Jobs\Component\DeleteComponentJob;
use App\Jobs\Component\SyncComponentDeployKeysJob;
use App\Jobs\Environment\DeleteEnvironmentJob;
use App\Jobs\EnvironmentDeployment\TriggerEnvironmentDeployFromWebhookJob;
use App\Jobs\GitHook\SyncRepositoryGitHookJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\GitHook;
use App\Models\Project;
use App\Models\SourceControl;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\BitbucketV2;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake('');
    Queue::fake();
    Event::fake([SocketEvent::class]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.github.com/repos/*/hooks/*' => Http::response([], 204),
        'https://api.github.com/repos/*/hooks' => Http::response(['id' => 123], 201),
        'https://api.github.com/repos/*' => Http::response([]),
    ]);
    $this->source = SourceControl::factory()->github()->create(['project_id' => $this->server->project_id, 'user_id' => $this->user->id]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'source_control_id' => $this->source->id, 'repository' => 'owner/shared', 'runtime' => 'custom']);
    $this->environment = Environment::factory()->for($this->application)->create(['branch' => 'main']);
});

function setEnvironmentAutoDeploy(Environment $environment, bool $enabled): Environment
{
    return app(UpdateEnvironment::class)->update($environment, [
        'name' => $environment->name, 'branch' => $environment->branch, 'runtime_version' => $environment->runtime_version, 'auto_deploy' => $enabled,
    ]);
}

test('two applications share one repository hook until their last auto deploy environment disables it', function () {
    $other = Application::factory()->create(['project_id' => $this->application->project_id, 'source_control_id' => $this->source->id, 'repository' => $this->application->repository, 'runtime' => 'custom']);
    $environment = Environment::factory()->for($other)->create();
    setEnvironmentAutoDeploy($this->environment, true);
    setEnvironmentAutoDeploy($environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    expect($hook->repository())->toBe('owner/shared')->and($hook->site_id)->toBeNull();
    Http::assertSentCount(1);
    setEnvironmentAutoDeploy($this->environment, false);
    $this->assertDatabaseHas('git_hooks', ['id' => $hook->id]);
    Http::assertSentCount(1);
    setEnvironmentAutoDeploy($environment, false);
    $this->assertDatabaseMissing('git_hooks', ['id' => $hook->id]);
    Http::assertSentCount(2);
});

test('repository hook reconciliation removes local custody when the remote hook is already absent', function () {
    setEnvironmentAutoDeploy($this->environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    $this->environment->forceFill(['auto_deploy' => false])->save();
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(['https://api.github.com/repos/owner/shared/hooks/123' => Http::response([], 404)]);

    app(SyncRepositoryGitHook::class)->sync($this->source, 'owner/shared');
    $this->assertDatabaseMissing('git_hooks', ['id' => $hook->id]);
    Http::assertSentCount(1);
});

test('repository hook ownership is exclusive and site hooks keep their repository lookup', function () {
    setEnvironmentAutoDeploy($this->environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    $hook->site_id = $this->site->id;
    expect(fn () => $hook->save())->toThrow(LogicException::class);
    $siteHook = GitHook::factory()->create(['site_id' => $this->site->id, 'source_control_id' => $this->source->id]);
    expect($siteHook->repository())->toBe($this->site->repository);
});

test('environment application and project deletion remove their last shared hook', function (string $owner) {
    setEnvironmentAutoDeploy($this->environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    match ($owner) {
        'environment' => app(DeleteEnvironment::class)->delete($this->environment, ['name' => $this->environment->name]),
        'application' => app(DeleteApplication::class)->delete($this->application, ['name' => $this->application->name]),
        'project' => $this->application->project->delete(),
    };
    $this->assertDatabaseMissing('git_hooks', ['id' => $hook->id]);
    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE' && $request->url() === 'https://api.github.com/repos/owner/shared/hooks/123');
})->with(['environment', 'application', 'project']);

test('changing repository or source control moves the shared hook', function (bool $changeSource) {
    setEnvironmentAutoDeploy($this->environment, true);
    $old = GitHook::query()->whereNotNull('repository')->sole();
    $source = $changeSource ? SourceControl::factory()->github()->create(['project_id' => $this->source->project_id, 'user_id' => $this->user->id]) : $this->source;
    app(UpdateApplication::class)->update($this->application, [
        'name' => $this->application->name, 'repository' => 'owner/changed', 'source_control_id' => $source->id,
    ], $this->user);
    $this->assertDatabaseMissing('git_hooks', ['id' => $old->id]);
    $this->assertDatabaseHas('git_hooks', ['source_control_id' => $source->id, 'repository' => 'owner/changed']);
})->with([false, true]);

test('repository pushes survive receipt and queue only matching enabled environments across applications', function () {
    $other = Application::factory()->create(['project_id' => $this->application->project_id, 'source_control_id' => $this->source->id, 'repository' => 'owner/shared']);
    $matching = Environment::factory()->for($other)->create(['branch' => 'main', 'auto_deploy' => true]);
    Environment::factory()->for($other)->create(['branch' => 'other', 'auto_deploy' => true]);
    Environment::factory()->for($other)->create(['branch' => 'main', 'auto_deploy' => false]);
    Environment::factory()->create(['branch' => 'main', 'auto_deploy' => true]);
    setEnvironmentAutoDeploy($this->environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    $this->postJson('/api/git-hooks?secret='.$hook->secret, ['ref' => 'refs/heads/main'])->assertOk();
    $this->assertDatabaseHas('git_hooks', ['id' => $hook->id]);
    Queue::assertPushed(TriggerEnvironmentDeployFromWebhookJob::class, 2);
    Queue::assertPushed(TriggerEnvironmentDeployFromWebhookJob::class, fn ($job): bool => $job->environmentId === $matching->id);
    $this->postJson('/api/git-hooks?secret=unknown', ['ref' => 'refs/heads/main'])->assertNotFound();
});

test('github app pushes use the same environment fanout without creating repository hooks', function () {
    $source = SourceControl::factory()->githubApp()->create();
    $this->application->update(['source_control_id' => $source->id]);
    setEnvironmentAutoDeploy($this->environment, true);
    $other = Application::factory()->create(['project_id' => $this->application->project_id, 'source_control_id' => $source->id, 'repository' => 'owner/shared']);
    Environment::factory()->for($other)->create(['branch' => 'main', 'auto_deploy' => true]);
    Environment::factory()->for($other)->create(['branch' => 'preview', 'auto_deploy' => true]);
    $payload = ['installation' => ['id' => (int) $source->external_identifier], 'repository' => ['full_name' => 'owner/shared'], 'ref' => 'refs/heads/main'];
    app(HandlePushWebhook::class)->handle($payload);
    Queue::assertPushed(TriggerEnvironmentDeployFromWebhookJob::class, 2);
    Queue::fake();
    app(HandlePushWebhook::class)->handle([...$payload, 'deleted' => true]);
    app(HandlePushWebhook::class)->handle([...$payload, 'ref' => 'refs/tags/main']);
    Queue::assertNotPushed(TriggerEnvironmentDeployFromWebhookJob::class);
    $this->assertDatabaseCount('git_hooks', 0);
    Http::assertNothingSent();
});

test('queued webhook triggers revalidate configuration and create anonymous runs', function (bool $disabled) {
    $component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    $this->environment->primary_component_id = $component->id;
    $this->environment->save();
    setEnvironmentAutoDeploy($this->environment, true);
    $job = new TriggerEnvironmentDeployFromWebhookJob($this->environment->id, $this->source->id, $this->application->repository, 'main');
    if ($disabled) {
        setEnvironmentAutoDeploy($this->environment, false);
    }
    $job->handle();
    if ($disabled) {
        $this->assertDatabaseCount('environment_deployments', 0);
    } else {
        $this->assertDatabaseHas('environment_deployments', ['environment_id' => $this->environment->id, 'triggered_by' => null, 'status' => EnvironmentDeploymentStatus::RUNNING]);
    }
})->with([false, true]);

test('webhook admission failures are recorded safely on an environment run', function () {
    $job = new TriggerEnvironmentDeployFromWebhookJob($this->environment->id, $this->source->id, $this->application->repository, 'main');
    $job->failed(new Exception('secret-token-and-provider-response'));
    $run = $this->environment->deployments()->sole();
    expect($run->status)->toBe(EnvironmentDeploymentStatus::FAILED)->and($run->triggered_by)->toBeNull()
        ->and($run->last_error)->toContain('Automatic deployment could not start')->not->toContain('secret-token');
    Event::assertDispatched(SocketEvent::class);
});

test('shared hook destruction errors bubble and retain the hook for retry', function () {
    setEnvironmentAutoDeploy($this->environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    Http::swap(new Factory);
    Http::fake(['https://api.github.com/repos/*/hooks/*' => Http::response([], 500)]);
    expect(fn () => setEnvironmentAutoDeploy($this->environment, false))->toThrow(FailedToDestroyGitHook::class);
    $this->assertDatabaseHas('git_hooks', ['id' => $hook->id]);
    Http::swap(new Factory);
    Http::fake(['https://api.github.com/repos/*/hooks/*' => Http::response([], 204)]);
    app(SyncRepositoryGitHook::class)->sync($this->source, $this->application->repository);
    $this->assertDatabaseMissing('git_hooks', ['id' => $hook->id]);
});

test('hook failures do not strand queued component key synchronization', function () {
    $component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    setEnvironmentAutoDeploy($this->environment, true);
    Http::swap(new Factory);
    Http::fake([
        'https://api.github.com/repos/owner/shared/hooks/*' => Http::response([], 500),
        'https://api.github.com/repos/owner/changed/hooks' => Http::response(['id' => 456], 201),
        'https://api.github.com/repos/*' => Http::response([]),
    ]);
    expect(fn () => app(UpdateApplication::class)->update($this->application, [
        'name' => $this->application->name, 'repository' => 'owner/changed', 'source_control_id' => $this->source->id,
    ], $this->user))->toThrow(FailedToDestroyGitHook::class);
    Queue::assertPushed(SyncComponentDeployKeysJob::class);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => ComponentStatus::PENDING]);
    $this->assertDatabaseHas('git_hooks', ['repository' => 'owner/changed', 'hook_id' => '456']);
});

test('hook failures do not prevent component teardown dispatch', function (bool $application) {
    Bus::fake();
    $component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    setEnvironmentAutoDeploy($this->environment, true);
    Http::swap(new Factory);
    Http::fake(['https://api.github.com/repos/*/hooks/*' => Http::response([], 500)]);
    expect(fn () => $application
        ? app(DeleteApplication::class)->delete($this->application, ['name' => $this->application->name])
        : app(DeleteEnvironment::class)->delete($this->environment, ['name' => $this->environment->name]))->toThrow(FailedToDestroyGitHook::class);
    Bus::assertChained([
        new DeleteComponentJob($component->id, $this->server->id),
        new DeleteEnvironmentJob($this->environment->id),
        ...($application ? [new DeleteApplicationJob($this->application->id)] : []),
    ]);
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'auto_deploy' => false]);
})->with([false, true]);

test('shared hooks ignore pushes after source control removal and toggles validate available sources', function () {
    setEnvironmentAutoDeploy($this->environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    $this->source->delete();
    $this->postJson('/api/git-hooks?secret='.$hook->secret, ['ref' => 'refs/heads/main'])->assertOk();
    Queue::assertNotPushed(TriggerEnvironmentDeployFromWebhookJob::class);
    expect(fn () => setEnvironmentAutoDeploy($this->environment, true))->toThrow(ValidationException::class);
    expect(fn () => $hook->fresh()->deployHook())->toThrow(RuntimeException::class);
    $hook->fresh()->destroyHook();
    $this->assertDatabaseMissing('git_hooks', ['id' => $hook->id]);
});

test('admission errors pass through the queue failure callback into a safe failed run', function () {
    setEnvironmentAutoDeploy($this->environment, true);
    $job = new TriggerEnvironmentDeployFromWebhookJob($this->environment->id, $this->source->id, $this->application->repository, 'main');
    $queueJob = Mockery::mock(Job::class);
    $queueJob->shouldReceive('fail')->once()->andReturnUsing(fn ($error) => $job->failed($error));
    $job->setJob($queueJob);
    $job->handle();
    $this->assertDatabaseHas('environment_deployments', ['environment_id' => $this->environment->id, 'status' => EnvironmentDeploymentStatus::FAILED, 'triggered_by' => null]);
});

test('deploy operations migration round trips and preserves site hooks', function () {
    $siteHook = GitHook::factory()->create(['site_id' => $this->site->id, 'source_control_id' => $this->source->id]);
    setEnvironmentAutoDeploy($this->environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    Http::fake();
    $migration = require database_path('migrations/2026_10_18_223613_add_environment_deployment_operations.php');
    $migration->down();
    $this->assertDatabaseHas('git_hooks', ['id' => $siteHook->id, 'site_id' => $this->site->id]);
    $this->assertDatabaseMissing('git_hooks', ['id' => $hook->id]);
    expect(collect(Schema::getColumns('git_hooks'))->firstWhere('name', 'site_id')['nullable'])->toBeFalse();
    $migration->up();
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'auto_deploy' => false]);
    expect(Schema::hasColumn('environment_deployments', 'rollback_of_id'))->toBeTrue()
        ->and(Schema::hasColumn('environment_deployments', 'last_error'))->toBeTrue();
    Http::assertNothingSent();
});

test('browser shaped settings can disable auto deploy during an active deployment', function () {
    $component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    $this->environment->primary_component_id = $component->id;
    $this->environment->save();
    setEnvironmentAutoDeploy($this->environment, true);
    $run = app(DeployEnvironment::class)->run($this->environment);
    $this->actingAs($this->user)->put('/applications/'.$this->application->id.'/environments/'.$this->environment->id, [
        'name' => $this->environment->name, 'branch' => 'main', 'runtime_version' => null,
        'primary_component_id' => (string) $component->id, 'auto_deploy' => false,
    ])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'auto_deploy' => false, 'primary_component_id' => $component->id]);
    expect($run->fresh()->status)->toBe(EnvironmentDeploymentStatus::RUNNING);
});

test('identical application updates and reconciliation recover failed hook moves', function (bool $destroyFails) {
    setEnvironmentAutoDeploy($this->environment, true);
    $input = ['name' => $this->application->name, 'repository' => 'owner/changed', 'source_control_id' => $this->source->id];
    Http::swap(new Factory);
    Http::fake([
        'https://api.github.com/repos/owner/shared/hooks/*' => Http::response([], $destroyFails ? 500 : 204),
        'https://api.github.com/repos/owner/changed/hooks' => Http::response(['id' => 456], $destroyFails ? 201 : 500),
        'https://api.github.com/repos/*' => Http::response([]),
    ]);
    expect(fn () => app(UpdateApplication::class)->update($this->application, $input, $this->user))->toThrow($destroyFails ? FailedToDestroyGitHook::class : FailedToDeployGitHook::class);
    Http::swap(new Factory);
    Http::fake([
        'https://api.github.com/repos/*/hooks/*' => Http::response([], 204),
        'https://api.github.com/repos/*/hooks' => Http::response(['id' => 456], 201),
        'https://api.github.com/repos/*' => Http::response([]),
    ]);
    app(UpdateApplication::class)->update($this->application, $input, $this->user);
    $this->artisan('git-hooks:sync-repositories')->assertSuccessful();
    foreach (Queue::pushed(SyncRepositoryGitHookJob::class) as $job) {
        $job->handle();
    }
    $this->assertDatabaseCount('git_hooks', 1);
    $this->assertDatabaseHas('git_hooks', ['repository' => 'owner/changed', 'hook_id' => '456']);
})->with([false, true]);

test('reconciliation retries revocation after the original owner was deleted', function (bool $application) {
    setEnvironmentAutoDeploy($this->environment, true);
    Http::swap(new Factory);
    Http::fake(['https://api.github.com/repos/*/hooks/*' => Http::response([], 500)]);
    expect(fn () => $application
        ? app(DeleteApplication::class)->delete($this->application, ['name' => $this->application->name])
        : app(DeleteEnvironment::class)->delete($this->environment, ['name' => $this->environment->name]))->toThrow(FailedToDestroyGitHook::class);
    $this->assertDatabaseMissing('environments', ['id' => $this->environment->id]);
    $this->assertDatabaseCount('git_hooks', 1);
    Http::swap(new Factory);
    Http::fake(['https://api.github.com/repos/*/hooks/*' => Http::response([], 204)]);
    $this->artisan('git-hooks:sync-repositories')->assertSuccessful();
    Queue::assertPushed(SyncRepositoryGitHookJob::class, 1);
    Queue::pushed(SyncRepositoryGitHookJob::class)->sole()->handle();
    $this->assertDatabaseCount('git_hooks', 0);
})->with([false, true]);

test('retrying project deletion reconciles hooks even after its environments were disabled', function () {
    setEnvironmentAutoDeploy($this->environment, true);
    $project = $this->application->project;
    Http::swap(new Factory);
    Http::fake(['https://api.github.com/repos/*/hooks/*' => Http::response([], 500)]);
    expect(fn () => $project->delete())->toThrow(FailedToDestroyGitHook::class);
    $this->assertDatabaseHas('projects', ['id' => $project->id]);
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'auto_deploy' => false]);
    Http::swap(new Factory);
    Http::fake(['https://api.github.com/repos/*/hooks/*' => Http::response([], 204)]);
    $project->delete();
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseCount('git_hooks', 0);
});

test('repository reconciliation deduplicates missing hooks and safely handles removed sources and failures', function () {
    $this->environment->auto_deploy = true;
    $this->environment->save();
    Environment::factory()->for($this->application)->create(['auto_deploy' => true]);
    $this->artisan('git-hooks:sync-repositories')->assertSuccessful();
    Queue::assertPushed(SyncRepositoryGitHookJob::class, 1);
    $job = Queue::pushed(SyncRepositoryGitHookJob::class)->sole();
    $job->handle();
    $this->assertDatabaseCount('git_hooks', 1);
    $this->source->delete();
    Http::fake();
    $job->handle();
    Http::assertNothingSent();
    Log::shouldReceive('error')->once()->with('Repository hook synchronization failed; scheduled reconciliation will retry.', ['source_control_id' => $this->source->id]);
    $job->failed(new Exception('private-token'));
    $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => str_contains($event->command ?? '', 'git-hooks:sync-repositories'));
    expect($event->expression)->toBe('*/5 * * * *')->and($event->expiresAt)->toBe(5);
});

test('repository push admission rechecks the source project scope and removes unauthorized hooks', function () {
    setEnvironmentAutoDeploy($this->environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    $job = new TriggerEnvironmentDeployFromWebhookJob($this->environment->id, $this->source->id, 'owner/shared', 'main');
    $this->source->project_id = Project::factory()->create()->id;
    $this->source->save();
    $this->postJson('/api/git-hooks?secret='.$hook->secret, ['ref' => 'refs/heads/main'])->assertOk();
    Queue::assertNotPushed(TriggerEnvironmentDeployFromWebhookJob::class);
    $job->handle();
    $this->assertDatabaseCount('environment_deployments', 0);
    app(SyncRepositoryGitHook::class)->sync($this->source, 'owner/shared');
    $this->assertDatabaseMissing('git_hooks', ['id' => $hook->id]);
});

test('repository hooks reject tags deletions and non push payloads', function (array $payload) {
    setEnvironmentAutoDeploy($this->environment, true);
    $hook = GitHook::query()->whereNotNull('repository')->sole();
    $this->postJson('/api/git-hooks?secret='.$hook->secret, $payload)->assertOk();
    Queue::assertNotPushed(TriggerEnvironmentDeployFromWebhookJob::class);
})->with([
    [['ref' => 'refs/tags/main']], [['ref' => 'refs/heads/main', 'deleted' => true]],
    [['ref' => 'refs/heads/main', 'after' => str_repeat('0', 40)]], [['ref' => 'main']], [[]],
]);

test('bitbucket repository pushes fan out all changed branches but not tags or deleted branches', function (string $provider) {
    $this->source->provider = $provider;
    $this->source->save();
    $this->environment->auto_deploy = true;
    $this->environment->save();
    Environment::factory()->for($this->application)->create(['branch' => 'preview', 'auto_deploy' => true]);
    $hook = new GitHook(['source_control_id' => $this->source->id, 'secret' => 'bitbucket-test', 'events' => ['push'], 'actions' => ['deploy']]);
    $hook->repository = 'owner/shared';
    $hook->save();
    $this->postJson('/api/git-hooks?secret='.$hook->secret, ['push' => ['changes' => [
        ['new' => ['type' => 'tag', 'name' => 'main']], ['new' => null, 'closed' => true],
        ['new' => ['type' => 'branch', 'name' => 'main']], ['new' => ['type' => 'branch', 'name' => 'preview']],
        ['new' => ['type' => 'branch', 'name' => 'main']],
    ]]])->assertOk();
    Queue::assertPushed(TriggerEnvironmentDeployFromWebhookJob::class, 2);
    Http::assertNothingSent();
})->with([Bitbucket::id(), BitbucketV2::id()]);
