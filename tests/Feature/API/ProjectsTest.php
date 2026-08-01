<?php

use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('create project', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', '/api/projects', [
        'name' => 'test',
    ])
        ->assertSuccessful();

    $this->assertDatabaseHas('projects', [
        'name' => 'test',
    ]);
});

test('see projects list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Project $project */
    $project = Project::factory()->create();
    $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $this->json('GET', '/api/projects')
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => $project->name,
        ]);
});

test('delete project', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Project $project */
    $project = Project::factory()->create();
    $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::OWNER,
    ]);

    $this->json('DELETE', '/api/projects/'.$project->id)
        ->assertSuccessful();

    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);
});

test('edit project', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Project $project */
    $project = Project::factory()->create();
    $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $this->json('PUT', "/api/projects/{$project->id}", [
        'name' => 'new-name',
    ])
        ->assertSuccessful();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'new-name',
    ]);
});

test('cannot delete last project', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('DELETE', "/api/projects/{$this->user->currentProject->id}")
        ->assertJsonValidationErrorFor('name');

    $this->assertDatabaseHas('projects', [
        'id' => $this->user->currentProject->id,
    ]);
});
