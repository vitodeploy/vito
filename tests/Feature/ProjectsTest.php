<?php

namespace Tests\Feature;

use App\Http\Livewire\Projects\CreateProject;
use App\Http\Livewire\Projects\EditProject;
use App\Http\Livewire\Projects\ProjectsList;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_project(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateProject::class)
            ->set('inputs.name', 'test')
            ->call('create')
            ->assertSuccessful();

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

        Livewire::test(ProjectsList::class)
            ->assertSee([
                $project->name,
            ]);
    }

    public function test_delete_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(ProjectsList::class)
            ->set('deleteId', $project->id)
            ->call('delete')
            ->assertSuccessful();

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

        Livewire::test(EditProject::class, [
            'project' => $project,
        ])
            ->set('inputs.name', 'test')
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'test',
        ]);
    }
}
