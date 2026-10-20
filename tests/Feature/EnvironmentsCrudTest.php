<?php

use App\Enums\EnvironmentStatus;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Models\Application;
use App\Models\Environment;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake();
    Queue::fake();
    Http::preventStrayRequests();
    Http::fake();
    $this->actingAs($this->user);
    $this->application = Application::factory()->create(['project_id' => $this->user->current_project_id]);
    $this->environment = Environment::factory()->for($this->application)->create(['name' => 'Production', 'slug' => 'production', 'runtime_version' => '22']);
    $this->environmentInput = ['name' => 'Preview Environment', 'branch' => 'preview', 'runtime_version' => '24'];
});

test('create environment derives its slug and ignores lifecycle and parent input', function () {
    $input = array_replace($this->environmentInput, [
        'slug' => 'untrusted', 'application_id' => Application::factory()->create()->id,
        'status' => 'deleting', 'primary_component_id' => 999,
    ]);

    $response = $this->post(route('environments.store', $this->application), $input)->assertSessionDoesntHaveErrors();

    $environment = $this->application->environments()->where('name', $input['name'])->sole();
    $response->assertRedirect(route('environments.show', [$this->application, $environment]));
    $this->assertDatabaseHas('environments', [
        'id' => $environment->id, 'application_id' => $this->application->id,
        'name' => $input['name'], 'slug' => 'preview-environment', 'branch' => $input['branch'],
        'runtime_version' => '24', 'primary_component_id' => null, 'status' => EnvironmentStatus::READY->value,
    ]);
    Queue::assertNothingPushed();
    Http::assertNothingSent();
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('update environment preserves slug and internal fields', function () {
    $input = array_replace($this->environmentInput, [
        'slug' => 'changed', 'application_id' => Application::factory()->create()->id,
        'status' => 'deleting', 'primary_component_initialized' => true,
    ]);

    $this->put(route('environments.update', [$this->application, $this->environment]), $input)->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('environments', [
        'id' => $this->environment->id, 'application_id' => $this->application->id,
        'name' => $input['name'], 'slug' => 'production', 'branch' => $input['branch'],
        'runtime_version' => '24', 'primary_component_id' => null, 'status' => EnvironmentStatus::READY->value,
    ]);
    $this->put(route('environments.update', [$this->application, $this->environment]), $input)->assertSessionDoesntHaveErrors();
});

test('environments validate names branches and runtime versions on create and update', function (string $field, mixed $value, bool $update) {
    $input = array_replace($this->environmentInput, [$field => $value]);
    $route = $update ? route('environments.update', [$this->application, $this->environment]) : route('environments.store', $this->application);

    $this->json($update ? 'PUT' : 'POST', $route, $input)->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('environments', 1);
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'name' => 'Production']);
})->with([
    ['name', ''], ['name', []], ['name', str_repeat('a', 256)],
    ['branch', ''], ['branch', []], ['branch', str_repeat('a', 256)],
    ['runtime_version', null], ['runtime_version', 'unsupported'], ['runtime_version', []],
])->with([false, true]);

test('versionless presets allow null versions but reject arbitrary versions', function (string $runtime, bool $update) {
    $this->application->update(['runtime' => $runtime]);
    $input = array_replace($this->environmentInput, ['runtime_version' => null]);
    $route = $update ? route('environments.update', [$this->application, $this->environment]) : route('environments.store', $this->application);

    $this->json($update ? 'PUT' : 'POST', $route, $input)->assertSessionDoesntHaveErrors()->assertRedirect();
    $this->assertDatabaseHas('environments', ['application_id' => $this->application->id, 'name' => $input['name'], 'runtime_version' => null]);
    $input['name'] = 'Another environment';
    $input['runtime_version'] = 'arbitrary';
    $this->json($update ? 'PUT' : 'POST', $route, $input)->assertUnprocessable()->assertJsonValidationErrors('runtime_version');
})->with(['static', 'custom'])->with([false, true]);

test('environment names and slugs are unique per application', function () {
    $route = route('environments.store', $this->application);
    $this->postJson($route, array_replace($this->environmentInput, ['name' => 'Production']))->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->postJson($route, array_replace($this->environmentInput, ['name' => 'Production!']))->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->postJson($route, array_replace($this->environmentInput, ['name' => '!!!']))->assertUnprocessable()->assertJsonValidationErrors('name');
    Environment::factory()->for($this->application)->create(['name' => $this->environmentInput['name']]);
    $this->putJson(route('environments.update', [$this->application, $this->environment]), $this->environmentInput)
        ->assertUnprocessable()->assertJsonValidationErrors('name');

    $other = Application::factory()->create(['project_id' => $this->user->current_project_id]);
    $this->post(route('environments.store', $other), array_replace($this->environmentInput, ['name' => 'Production']))->assertSessionDoesntHaveErrors();
});

test('delete environment requires its name and retains its application and siblings', function () {
    $sibling = Environment::factory()->for($this->application)->create();
    $route = route('environments.destroy', [$this->application, $this->environment]);

    $this->deleteJson($route, ['name' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id]);
    $this->delete($route, ['name' => $this->environment->name])->assertRedirect(route('applications.show', $this->application));

    $this->assertDatabaseMissing('environments', ['id' => $this->environment->id]);
    $this->assertDatabaseHas('environments', ['id' => $sibling->id]);
    $this->assertDatabaseHas('applications', ['id' => $this->application->id]);
    Queue::assertNothingPushed();
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('environment page shares its authorized application and environment', function () {
    $this->get(route('environments.show', [$this->application, $this->environment]))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('applications/environments/show')
            ->where('application.id', $this->application->id)->where('environment.id', $this->environment->id)
            ->where('environment.application_id', $this->application->id)->where('environment.slug', 'production')
            ->where('environment.runtime_version', '22')->where('environment.status', 'ready')
            ->where('environment.status_color', 'success')->where('can.update', true)->where('can.delete', true));
});

test('environment links switch the project only for authorized users', function () {
    $project = Project::factory()->create();
    $project->users()->create(['user_id' => $this->user->id, 'role' => UserRole::ADMIN]);
    $this->application->update(['project_id' => $project->id]);

    $this->get(route('environments.show', [$this->application, $this->environment]))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.currentProject.id', $project->id));

    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'current_project_id' => $project->id]);
});

test('environment scoped bindings reject mismatched parent ids', function () {
    $other = Application::factory()->create(['project_id' => $this->user->current_project_id]);

    $this->get(route('environments.show', [$other, $this->environment]))->assertNotFound();
    $this->putJson(route('environments.update', [$other, $this->environment]), $this->environmentInput)->assertNotFound();
    $this->deleteJson(route('environments.destroy', [$other, $this->environment]), ['name' => $this->environment->name])->assertNotFound();
    $this->assertDatabaseHas('environments', ['id' => $this->environment->id, 'name' => 'Production']);
});

test('read only members can view but cannot mutate environments', function () {
    $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);

    $this->get(route('environments.show', [$this->application, $this->environment]))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('can.update', false)->where('can.delete', false));
    $this->postJson(route('environments.store', $this->application), $this->environmentInput)->assertForbidden();
    $this->putJson(route('environments.update', [$this->application, $this->environment]), $this->environmentInput)->assertForbidden();
    $this->deleteJson(route('environments.destroy', [$this->application, $this->environment]), ['name' => $this->environment->name])->assertForbidden();
});

test('environment endpoints reject other projects and guests', function (bool $guest) {
    if ($guest) {
        auth()->logout();
    } else {
        $this->application->update(['project_id' => Project::factory()->create()->id]);
    }
    $status = $guest ? 401 : 403;

    $this->getJson(route('environments.show', [$this->application, $this->environment]))->assertStatus($status);
    $this->postJson(route('environments.store', $this->application), $this->environmentInput)->assertStatus($status);
    $this->putJson(route('environments.update', [$this->application, $this->environment]), $this->environmentInput)->assertStatus($status);
    $this->deleteJson(route('environments.destroy', [$this->application, $this->environment]))->assertStatus($status);
    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'current_project_id' => $this->user->current_project_id]);
})->with([false, true]);
