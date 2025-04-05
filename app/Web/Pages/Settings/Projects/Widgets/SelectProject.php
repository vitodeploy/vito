<?php

namespace App\Web\Pages\Settings\Projects\Widgets;

use App\Models\Project;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class SelectProject extends Widget
{
    protected static string $view = 'widgets.select-project';

    public ?Project $currentProject = null;

    /**
     * @var Collection<int, Project>
     */
    public Collection $projects;

    public int|string|null $project = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->currentProject = $user->currentProject;
        $this->projects = $user->allProjects()->get();
    }

    public function updateProject(Project $project): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorize('view', $project);
        $user->update(['current_project_id' => $project->id]);

        $this->redirect('/');
    }
}
