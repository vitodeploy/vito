<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_project(): void
    {
        $this->actingAs($this->user);

        $this->post(route('projects.store'), [
            'name' => 'create-project-test',
        ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('projects'));

        $this->assertDatabaseHas('projects', [
            'name' => 'create-project-test',
        ]);

        $this->assertEquals($this->user->refresh()->current_project_id, Project::query()->where('name', 'create-project-test')->first()->id);
    }

    public function test_see_projects_list(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $project->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $this->get(route('projects'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('projects/index'));

    }

    public function test_no_permission_to_delete_project(): void
    {
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
    }

    public function test_delete_project(): void
    {
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
    }

    public function test_no_permission_to_edit_project(): void
    {
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
    }

    public function test_edit_project(): void
    {
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
    }

    public function test_cannot_delete_last_project(): void
    {
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
    }
}
