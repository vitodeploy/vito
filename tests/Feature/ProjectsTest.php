<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Web\Pages\Settings\Projects\Index;
use App\Web\Pages\Settings\Projects\Settings;
use App\Web\Pages\Settings\Projects\Widgets\UpdateProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_project(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Index::class)
            ->callAction('create', [
                'name' => 'test',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('projects', [
            'name' => 'test',
        ]);
    }

    public function test_see_projects_list(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $this->user->projects()->attach($project);

        $this->get(Index::getUrl())
            ->assertSuccessful()
            ->assertSee($project->name);
    }

    public function test_delete_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $this->user->projects()->attach($project);

        Livewire::test(Settings::class, [
            'project' => $project,
        ])
            ->callAction('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_edit_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $this->user->projects()->attach($project);

        Livewire::test(UpdateProject::class, [
            'project' => $project,
        ])
            ->fill([
                'name' => 'new-name',
            ])
            ->call('submit')
            ->assertSuccessful();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'new-name',
        ]);
    }

    public function test_cannot_delete_last_project(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Settings::class, [
            'project' => $this->user->currentProject,
        ])
            ->callAction('delete')
            ->assertNotified('Cannot delete the last project.');

        $this->assertDatabaseHas('projects', [
            'id' => $this->user->currentProject->id,
        ]);
    }
}
