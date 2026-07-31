<?php

use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
