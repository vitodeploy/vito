<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_project(): void
    {
        $this->actingAs($this->user);

        $this->post(route('projects.create'), [
            'name' => 'test',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('projects', [
            'name' => 'test',
        ]);
    }

    public function test_see_projects_list(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(route('projects'))
            ->assertSuccessful()
            ->assertSee($project->name);
    }

    public function test_delete_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('projects.delete', $project))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_edit_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->post(route('projects.update', $project), [
            'name' => 'new-name',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'new-name',
        ]);
    }

    public function test_cannot_delete_last_project(): void
    {
        $this->actingAs($this->user);

        $this->delete(route('projects.delete', [
            'project' => $this->user->currentProject,
        ]))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'Cannot delete the last project.');
    }
}
