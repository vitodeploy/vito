<?php

namespace App\Web\Pages\Settings\Projects\Widgets;

use App\Models\Project;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SelectProject extends Widget
{
    protected static string $view = 'widgets.select-project';

    public ?Project $currentProject;

    public Collection $projects;

    public int|string|null $project;

    public function mount(): void
    {
        $this->currentProject = auth()->user()->currentProject;
        $this->projects = Project::query()
            ->where(function (Builder $query) {
                if (auth()->user()->isAdmin()) {
                    return;
                }
                $query->where('user_id', auth()->id())
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', auth()->id()));
            })
            ->get();
    }

    public function updateProject(Project $project): void
    {
        $this->authorize('view', $project);
        auth()->user()->update(['current_project_id' => $project->id]);

        $this->redirect('/');
    }
}
