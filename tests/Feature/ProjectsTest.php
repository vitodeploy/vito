<?php

use App\Actions\Projects\DeleteProject;
use App\Enums\EnvironmentDeploymentStatus;
use App\Enums\UserRole;
use App\Exceptions\FailedToDestroyGitHook;
use App\Facades\SSH;
use App\Models\Application;
use App\Models\Component;
use App\Models\Database;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Models\EnvironmentDeployment;
use App\Models\GitHook;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerProvider;
use App\Models\Site;
use App\Models\SourceControl;
use App\ServerProviders\Custom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('create project', function () {
    $this->actingAs($this->user);

    $this->post(route('projects.store'), [
        'name' => 'create-project-test',
    ])
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('projects'));

    $this->assertDatabaseHas('projects', [
        'name' => 'create-project-test',
    ]);

    expect(Project::query()->where('name', 'create-project-test')->first()->id)->toEqual($this->user->refresh()->current_project_id);
});

test('see projects list', function () {
    $this->actingAs($this->user);

    $project = Project::factory()->create();

    $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $this->get(route('projects'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('projects/index'));
});

test('no permission to delete project', function () {
    $this->actingAs($this->user);

    $project = Project::factory()->create();

    $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $this->delete(route('projects.destroy', $project), [
        'name' => $project->name,
    ])
        ->assertForbidden();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
    ]);
});

test('delete project', function () {
    $this->actingAs($this->user);

    $this->user->ensureHasDefaultProject();

    $project = Project::factory()->create(['name' => 'new-project']);

    $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::OWNER,
    ]);

    $this->delete(route('projects.destroy', $project), [
        'name' => $project->name,
    ])
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('projects'));

    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);
});

test('no permission to edit project', function () {
    $this->actingAs($this->user);

    $project = Project::factory()->create();

    $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::USER,
    ]);

    $this->patch(route('projects.update', $project), [
        'name' => 'new-name',
    ])
        ->assertForbidden();
});

test('project deletion checks every application guard before removing legacy servers', function (string $blocker) {
    SSH::fake();
    Http::preventStrayRequests();
    Queue::fake();
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $legacy = Server::factory()->for($project)->create();
    $site = Site::factory()->for($legacy)->create();
    $blocked = Server::factory()->for($project)->create();
    $application = Application::factory()->for($project)->create();
    $environment = Environment::factory()->for($application)->create();
    if ($blocker === 'resource') {
        $database = Database::factory()->for($blocked)->create();
        EnvironmentAttachment::factory()->for($environment)->create([
            'server_id' => $blocked->id, 'attachable_id' => $database->id,
        ]);
    } else {
        Component::factory()->for($environment)->for($blocked)->create();
        EnvironmentDeployment::factory()->for($environment)->create(['status' => EnvironmentDeploymentStatus::RUNNING]);
    }
    $provider = new class(new ServerProvider, $legacy) extends Custom
    {
        public static int $deletions = 0;

        public function delete(): void
        {
            self::$deletions++;
        }
    };
    $provider::$deletions = 0;
    config(['server-provider.providers.custom.handler' => $provider::class]);
    $keys = $legacy->sshKey();
    File::ensureDirectoryExists(dirname($keys['private_key_path']));
    File::put($keys['private_key_path'], 'existing-private-key-fixture');
    File::put($keys['public_key_path'], 'existing-public-key-fixture');

    $this->actingAs($this->user)->delete(route('projects.destroy', $project), ['name' => $project->name])
        ->assertSessionHasErrors('server');

    $this->assertDatabaseHas('projects', ['id' => $project->id]);
    $this->assertDatabaseHas('servers', ['id' => $legacy->id]);
    $this->assertDatabaseHas('servers', ['id' => $blocked->id]);
    $this->assertDatabaseHas('sites', ['id' => $site->id]);
    expect(File::get($keys['private_key_path']))->toBe('existing-private-key-fixture')
        ->and(File::get($keys['public_key_path']))->toBe('existing-public-key-fixture')
        ->and($provider::$deletions)->toBe(0)
        ->and(SSH::getExecutedCommands())->toBe([]);
    Queue::assertNothingPushed();
    Http::assertNothingSent();
})->with(['resource', 'deployment']);

test('project deletion removes legacy servers and idle components after preflight', function () {
    SSH::fake();
    Http::preventStrayRequests();
    Queue::fake();
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $legacy = Server::factory()->for($project)->create();
    $site = Site::factory()->for($legacy)->create();
    $server = Server::factory()->for($project)->create();
    $application = Application::factory()->for($project)->create(['source_control_id' => null]);
    $component = Component::factory()->for(Environment::factory()->for($application))->for($server)->create();

    app(DeleteProject::class)->delete($this->user, $project, ['name' => $project->name]);

    foreach ([$project, $legacy, $site, $server, $application, $component] as $removed) {
        $this->assertDatabaseMissing($removed->getTable(), ['id' => $removed->id]);
    }
});

test('project deletion reconciles failing repository hooks before deleting servers', function () {
    SSH::fake();
    Queue::fake();
    Http::fake(['https://api.github.com/repos/owner/repo/hooks/123' => Http::response([], 500)]);
    Http::preventStrayRequests();
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $server = Server::factory()->for($project)->create();
    $site = Site::factory()->for($server)->create();
    $source = SourceControl::factory()->github()->for($project)->create();
    $application = Application::factory()->for($project)->create(['source_control_id' => $source->id, 'repository' => 'owner/repo']);
    Environment::factory()->for($application)->create(['auto_deploy' => true]);
    $hook = GitHook::factory()->create(['site_id' => null, 'source_control_id' => $source->id, 'repository' => 'owner/repo', 'hook_id' => '123']);

    expect(fn () => app(DeleteProject::class)->delete($this->user, $project, ['name' => $project->name]))->toThrow(FailedToDestroyGitHook::class);

    foreach ([$project, $server, $site, $application, $hook] as $retained) {
        $this->assertDatabaseHas($retained->getTable(), ['id' => $retained->id]);
    }
    expect(SSH::getExecutedCommands())->toBe([]);
    Queue::assertNothingPushed();
});

test('project deletion preserves completed hook cleanup when another repository fails', function () {
    SSH::fake();
    Queue::fake();
    Http::fake([
        'https://api.github.com/repos/owner/*/hooks/*' => Http::sequence()->push([], 204)->push([], 500),
    ]);
    Http::preventStrayRequests();
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $server = Server::factory()->for($project)->create();
    $site = Site::factory()->for($server)->create();
    $source = SourceControl::factory()->github()->for($project)->create();
    $environments = [];
    $hooks = [];
    foreach (['first' => '123', 'second' => '456'] as $repository => $hookId) {
        $application = Application::factory()->for($project)->create([
            'source_control_id' => $source->id, 'repository' => 'owner/'.$repository,
        ]);
        $environments[] = Environment::factory()->for($application)->create(['auto_deploy' => true]);
        $hooks[] = GitHook::factory()->create([
            'site_id' => null, 'source_control_id' => $source->id,
            'repository' => 'owner/'.$repository, 'hook_id' => $hookId,
        ]);
    }

    expect(fn () => app(DeleteProject::class)->delete($this->user, $project, ['name' => $project->name]))
        ->toThrow(FailedToDestroyGitHook::class);

    $completedUrl = Http::recorded()->first()[0]->url();
    foreach ($hooks as $hook) {
        if (str_ends_with($completedUrl, '/'.$hook->hook_id)) {
            $this->assertDatabaseMissing('git_hooks', ['id' => $hook->id]);
        } else {
            $this->assertDatabaseHas('git_hooks', ['id' => $hook->id, 'hook_id' => $hook->hook_id]);
        }
    }
    foreach ([$project, $server, $site] as $retained) {
        $this->assertDatabaseHas($retained->getTable(), ['id' => $retained->id]);
    }
    foreach ($environments as $environment) {
        $this->assertDatabaseHas('environments', ['id' => $environment->id, 'auto_deploy' => false]);
    }
    Http::assertSentCount(2);
    expect(SSH::getExecutedCommands())->toBe([]);
    Queue::assertNothingPushed();
});

test('project deletion rechecks application guards after repository hook cleanup', function () {
    SSH::fake();
    Queue::fake();
    Http::preventStrayRequests();
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    $server = Server::factory()->for($project)->create();
    $site = Site::factory()->for($server)->create();
    $source = SourceControl::factory()->github()->for($project)->create();
    $application = Application::factory()->for($project)->create(['source_control_id' => $source->id, 'repository' => 'owner/repo']);
    $environment = Environment::factory()->for($application)->create(['auto_deploy' => true]);
    Component::factory()->for($environment)->for($server)->create();
    $hook = GitHook::factory()->create(['site_id' => null, 'source_control_id' => $source->id, 'repository' => 'owner/repo', 'hook_id' => '123']);
    Http::fake(['https://api.github.com/repos/owner/repo/hooks/123' => function () use ($environment) {
        EnvironmentDeployment::factory()->for($environment)->create(['status' => EnvironmentDeploymentStatus::RUNNING]);

        return Http::response([], 204);
    }]);

    $this->actingAs($this->user)->delete(route('projects.destroy', $project), ['name' => $project->name])
        ->assertSessionHasErrors('server');

    foreach ([$project, $server, $site, $application] as $retained) {
        $this->assertDatabaseHas($retained->getTable(), ['id' => $retained->id]);
    }
    $this->assertDatabaseMissing('git_hooks', ['id' => $hook->id]);
    Http::assertSentCount(1);
    expect(SSH::getExecutedCommands())->toBe([]);
    Queue::assertNothingPushed();
});

test('edit project', function () {
    $this->actingAs($this->user);

    $project = Project::factory()->create();

    $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $this->patch(route('projects.update', $project), [
        'name' => 'new-name',
    ])
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('projects'));

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'new-name',
    ]);
});

test('cannot delete last project', function () {
    $this->actingAs($this->user);

    $this->delete(route('projects.destroy', $this->user->currentProject->id), [
        'name' => $this->user->currentProject->name,
    ])
        ->assertSessionHasErrors([
            'name' => 'Cannot delete the last project.',
        ]);

    $this->assertDatabaseHas('projects', [
        'id' => $this->user->currentProject->id,
    ]);
});
