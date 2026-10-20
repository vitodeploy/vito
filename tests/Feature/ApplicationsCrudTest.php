<?php

use App\Actions\Application\CreateApplication;
use App\Enums\EnvironmentStatus;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Models\Application;
use App\Models\Environment;
use App\Models\Project;
use App\Models\SourceControl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake();
    Queue::fake();
    Http::preventStrayRequests();
    $this->repositoryResponse = Http::response(['full_name' => 'organization/repository']);
    Http::fake(['api.github.com/repos/*' => fn () => $this->repositoryResponse]);
    $this->actingAs($this->user);
    $this->sourceControl = SourceControl::factory()->github()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);
    $this->applicationInput = [
        'name' => 'Example application',
        'source_control_id' => $this->sourceControl->id,
        'repository' => 'organization/repository',
        'runtime' => 'node',
        'environment' => ['name' => 'Production', 'branch' => 'main', 'runtime_version' => '22'],
    ];
});

test('create application and its first environment without server work', function (string $runtime) {
    $preset = (new Application(['runtime' => $runtime]))->preset();
    $input = $this->applicationInput;
    $input['runtime'] = $runtime;
    $input['environment']['runtime_version'] = $preset->versions()[0] ?? null;
    $input['project_id'] = Project::factory()->create()->id;
    $input['environment']['status'] = EnvironmentStatus::DELETING->value;
    $input['environment']['slug'] = 'untrusted';
    $input['environment']['primary_component_id'] = 123;

    $response = $this->post(route('applications.store'), $input)->assertSessionDoesntHaveErrors();

    $application = Application::query()->sole();
    $response->assertRedirect(route('applications.show', $application));
    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'project_id' => $this->user->current_project_id,
        'name' => $input['name'],
        'repository' => $input['repository'],
        'source_control_id' => $this->sourceControl->id,
        'runtime' => $runtime,
    ]);
    $this->assertDatabaseHas('environments', [
        'application_id' => $application->id,
        'name' => 'Production',
        'slug' => 'production',
        'branch' => 'main',
        'runtime_version' => $input['environment']['runtime_version'],
        'status' => EnvironmentStatus::READY->value,
        'primary_component_id' => null,
    ]);
    $this->assertDatabaseCount('components', 0);
    Queue::assertNothingPushed();
    expect(SSH::getExecutedCommands())->toBeEmpty();
    Http::assertSentCount(1);
})->with(['node', 'php', 'bun', 'static', 'custom', 'laravel']);

test('application action returns the created application and accepts dotted preset ids', function () {
    $runtimes = config('component.runtimes');
    $runtimes['plugin.node'] = $runtimes['node'];
    config(['component.runtimes' => $runtimes]);
    $input = array_replace($this->applicationInput, ['runtime' => 'plugin.node']);

    $application = app(CreateApplication::class)->create($this->user->currentProject, $input, $this->user);

    expect($application)->toBeInstanceOf(Application::class);
    $this->assertDatabaseHas('applications', ['id' => $application->id, 'runtime' => 'plugin.node']);
});

test('application creation validates input and rolls back its first environment', function (string $field, mixed $value) {
    $input = $this->applicationInput;
    data_set($input, $field, $value);

    $this->postJson(route('applications.store'), $input)->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('applications', 0);
    $this->assertDatabaseCount('environments', 0);
})->with([
    ['name', ''], ['name', ['invalid']], ['name', str_repeat('a', 256)],
    ['source_control_id', null], ['source_control_id', 99999], ['source_control_id', ['invalid']],
    ['repository', ''], ['repository', ['invalid']], ['repository', str_repeat('a', 256)],
    ['runtime', 'unknown'], ['runtime', ['node']], ['environment', 'invalid'],
    ['environment.name', ''], ['environment.name', '!!!'], ['environment.branch', ''],
    ['environment.runtime_version', null], ['environment.runtime_version', 'unsupported'],
]);

test('application names are unique within a project on create and update', function (bool $update) {
    Application::factory()->create(['project_id' => $this->user->current_project_id, 'name' => $this->applicationInput['name']]);
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);

    $this->json($update ? 'PUT' : 'POST', $update ? route('applications.update', $application) : route('applications.store'), $this->applicationInput)
        ->assertUnprocessable()->assertJsonValidationErrors('name');
})->with([false, true]);

test('application names may be reused in different projects and global source controls are accepted', function () {
    Application::factory()->create(['name' => $this->applicationInput['name']]);
    $this->sourceControl->update(['project_id' => null]);

    $this->post(route('applications.store'), $this->applicationInput)->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('applications', ['project_id' => $this->user->current_project_id, 'name' => $this->applicationInput['name']]);
});

test('applications reject foreign deleted and unusable source controls on create and update', function (string $kind, bool $update) {
    if ($kind === 'foreign') {
        $this->sourceControl->update(['project_id' => Project::factory()->create()->id]);
    } elseif ($kind === 'deleted') {
        $this->sourceControl->delete();
    } else {
        config(['source-control.providers.github.usable_for_sites' => false]);
    }
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);

    $this->json($update ? 'PUT' : 'POST', $update ? route('applications.update', $application) : route('applications.store'), $this->applicationInput)
        ->assertUnprocessable()->assertJsonValidationErrors('source_control_id');

    Http::assertNothingSent();
})->with(['foreign', 'deleted', 'unusable'])->with([false, true]);

test('applications authorize source control ownership before contacting its provider', function (bool $update) {
    $this->sourceControl->update(['user_id' => User::factory()->create()->id]);
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);

    $this->json($update ? 'PUT' : 'POST', $update ? route('applications.update', $application) : route('applications.store'), $this->applicationInput)
        ->assertForbidden();

    Http::assertNothingSent();
})->with([false, true]);

test('applications report repository access failures on create and update', function (int $status, string $field, bool $update) {
    $this->repositoryResponse = Http::response([], $status);
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);
    $original = $application->getAttributes();

    $this->json($update ? 'PUT' : 'POST', $update ? route('applications.update', $application) : route('applications.store'), $this->applicationInput)
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseHas('applications', $original);
    $this->assertDatabaseCount('applications', 1);
    $this->assertDatabaseCount('environments', 0);
})->with([[401, 'source_control_id'], [403, 'repository'], [404, 'repository']])->with([false, true]);

test('applications reject unsafe and non-repository paths before contacting providers', function (string $repository, bool $update) {
    $input = array_replace($this->applicationInput, ['repository' => $repository]);
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);
    $original = $application->getAttributes();

    $this->json($update ? 'PUT' : 'POST', $update ? route('applications.update', $application) : route('applications.store'), $input)
        ->assertUnprocessable()->assertJsonValidationErrors('repository');

    $this->assertDatabaseHas('applications', $original);
    $this->assertDatabaseCount('applications', 1);
    Http::assertNothingSent();
})->with([
    '0', 'organization/repository/branches', '../user/repos', 'organization/..', './repository',
    'organization//repository', 'organization/repository/', 'organization/repository?query=value',
    'organization/repository#fragment', 'organization/%2e%2e', 'https://github.com/organization/repository',
    'organization\\repository',
])->with([false, true]);

test('application repository rules preserve gitlab subgroups and numeric project ids', function (string $repository, bool $update) {
    $this->sourceControl->update(['provider' => 'gitlab', 'url' => 'https://gitlab.com/']);
    Http::fake(['gitlab.com/api/v4/projects/*/repository/commits' => Http::response([['id' => 'commit']])]);
    $input = array_replace($this->applicationInput, ['repository' => $repository]);
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);

    $this->json($update ? 'PUT' : 'POST', $update ? route('applications.update', $application) : route('applications.store'), $input)
        ->assertRedirect()->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('applications', ['name' => $input['name'], 'repository' => $repository, 'source_control_id' => $this->sourceControl->id]);
    Http::assertSentCount(1);
})->with(['organization/sub-group/repository.name', '1234'])->with([false, true]);

test('application provider http failures bubble up without mutations', function (int $status, bool $update) {
    $this->withoutExceptionHandling();
    $this->repositoryResponse = Http::response([], $status);
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);
    $original = $application->getAttributes();

    expect(fn () => $this->json($update ? 'PUT' : 'POST', $update ? route('applications.update', $application) : route('applications.store'), $this->applicationInput))
        ->toThrow(RequestException::class);

    $this->assertDatabaseHas('applications', $original);
    $this->assertDatabaseCount('applications', 1);
    $this->assertDatabaseCount('environments', 0);
    Queue::assertNothingPushed();
})->with([400, 422, 429, 500, 503])->with([false, true]);

test('unexpected provider failures bubble up without persisting an application', function () {
    $this->repositoryResponse = fn () => throw new RuntimeException('Provider unavailable');

    expect(fn () => app(CreateApplication::class)->create($this->user->currentProject, $this->applicationInput, $this->user))
        ->toThrow(RuntimeException::class, 'Provider unavailable');

    $this->assertDatabaseCount('applications', 0);
});

test('update application changes only its editable fields', function () {
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);
    $environment = Environment::factory()->for($application)->create();
    $input = array_replace($this->applicationInput, [
        'runtime' => 'php',
        'project_id' => Project::factory()->create()->id,
        'repository' => 'organization/another-repository',
    ]);

    $this->put(route('applications.update', $application), $input)->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'project_id' => $application->project_id,
        'runtime' => 'node',
        'name' => $input['name'],
        'repository' => $input['repository'],
        'source_control_id' => $this->sourceControl->id,
    ]);
    expect($environment->fresh()->name)->toBe($environment->name);
    $this->put(route('applications.update', $application), $input)->assertSessionDoesntHaveErrors();
});

test('delete application requires its name and cascades environments', function () {
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);
    $environment = Environment::factory()->for($application)->create();

    $this->deleteJson(route('applications.destroy', $application), ['name' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->assertDatabaseHas('applications', ['id' => $application->id]);
    $this->delete(route('applications.destroy', $application), ['name' => $application->name])->assertRedirect(route('applications'));

    $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    $this->assertDatabaseMissing('environments', ['id' => $environment->id]);
    Queue::assertNothingPushed();
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('application index is project scoped searchable and sortable', function () {
    $alpha = Application::factory()->create(['project_id' => $this->user->current_project_id, 'name' => 'Alpha']);
    $beta = Application::factory()->create(['project_id' => $this->user->current_project_id, 'name' => 'Beta']);
    Environment::factory()->for($alpha)->create();
    Application::factory()->create(['name' => 'Foreign']);

    $this->get(route('applications', ['sort' => 'name']))->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('applications/index')->has('applications.columns')->has('applications.meta')
        ->has('applications.data', 2)->where('applications.data.0.id', $alpha->id)
        ->where('applications.data.0._status_enum_color', 'success')
        ->where('applications.data.0.environments_count', 1)->where('canCreate', true));
    $this->get(route('applications', ['search' => 'Beta']))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('applications.data', 1)->where('applications.data.0.id', $beta->id));
});

test('application pages share whitelisted resources and only authorized source controls', function () {
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);
    $environment = Environment::factory()->for($application)->create();
    SourceControl::factory()->github()->create(['user_id' => $this->user->id, 'project_id' => Project::factory()->create()->id]);
    SourceControl::factory()->github()->create(['project_id' => $this->user->current_project_id]);

    $this->get(route('applications.show', $application))->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('applications/show')->where('application.id', $application->id)
        ->where('application.source_control_id', null)->missing('application.sourceControl')
        ->has('environments', 1)->where('environments.0.id', $environment->id)
        ->where('environments.0.status', 'ready')->where('environments.0.status_color', 'success')
        ->where('environments.0.primary_component_id', null)->where('environments.0.components_count', 0)->has('sourceControls', 1)
        ->where('sourceControls.0.id', $this->sourceControl->id)->missing('sourceControls.0.provider_data')
        ->missing('sourceControls.0.access_token')->where('can.update', true));
});

test('authorized application links switch the current project consistently', function () {
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::ADMIN]);
    $application = Application::factory()->for($project)->create();

    $this->get(route('applications.show', $application))->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('application.project_id', $project->id)->where('auth.currentProject.id', $project->id));

    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'current_project_id' => $project->id]);
});

test('read only members can view applications but cannot mutate them', function () {
    $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $application = Application::factory()->create(['project_id' => $this->user->current_project_id]);

    $this->get(route('applications'))->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page->where('canCreate', false));
    $this->get(route('applications.show', $application))->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('can.update', false)->where('can.delete', false)->where('can.createEnvironment', false));
    $this->postJson(route('applications.store'), $this->applicationInput)->assertForbidden();
    $this->putJson(route('applications.update', $application), $this->applicationInput)->assertForbidden();
    $this->deleteJson(route('applications.destroy', $application), ['name' => $application->name])->assertForbidden();
    Http::assertNothingSent();
});

test('application endpoints reject other projects without switching the current project', function () {
    $application = Application::factory()->create();
    $projectId = $this->user->current_project_id;

    $this->get(route('applications.show', $application))->assertForbidden();
    $this->putJson(route('applications.update', $application), $this->applicationInput)->assertForbidden();
    $this->deleteJson(route('applications.destroy', $application), ['name' => $application->name])->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'current_project_id' => $projectId]);
    $this->assertDatabaseHas('applications', ['id' => $application->id]);
});

test('application endpoints require authentication', function () {
    auth()->logout();
    $application = Application::factory()->create();

    $this->getJson(route('applications'))->assertUnauthorized();
    $this->getJson(route('applications.show', $application))->assertUnauthorized();
    $this->postJson(route('applications.store'), $this->applicationInput)->assertUnauthorized();
    $this->putJson(route('applications.update', $application), $this->applicationInput)->assertUnauthorized();
    $this->deleteJson(route('applications.destroy', $application))->assertUnauthorized();
});
