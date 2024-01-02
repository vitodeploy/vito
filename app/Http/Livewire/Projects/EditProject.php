<?php

namespace App\Http\Livewire\Projects;

use App\Actions\Projects\UpdateProject;
use App\Models\Project;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EditProject extends Component
{
    use RefreshComponentOnBroadcast;

    public Project $project;

    public array $inputs = [];

    public function save(): void
    {
        app(UpdateProject::class)->update($this->project, $this->inputs);

        $this->redirect(route('projects'));
    }

    public function mount(): void
    {
        $this->inputs = [
            'name' => $this->project->name,
        ];
    }

    public function render(): View
    {
        return view('livewire.projects.edit-project');
    }
}
