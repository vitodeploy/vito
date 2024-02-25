<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JsonException;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws JsonException
     */
    public function test_create_project(): void
    {
        $this->actingAs($this->user);

        $this->post(route('projects.create'), [
            'name' => 'test',
        ])->assertSessionHasNoErrors();

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
            ->assertSee($project->name);
    }

    /**
     * @throws JsonException
     */
    public function test_delete_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('projects.delete', $project))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_edit_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->post(route('projects.update', $project), [
            'name' => 'new-name',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'new-name',
        ]);
    }
}
