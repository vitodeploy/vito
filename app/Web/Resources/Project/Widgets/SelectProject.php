<?php

namespace App\Web\Resources\Project\Widgets;

use App\Models\Project;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class SelectProject extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'web.project.widgets.select-project';

    public int|string|null $project;

    protected function getFormSchema(): array
    {
        $options = Project::query()
            ->where(function (Builder $query) {
                if (auth()->user()->isAdmin()) {
                    return;
                }

                $query->where('user_id', auth()->id())
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', auth()->id()));
            })
            ->get()
            ->mapWithKeys(fn ($project) => [$project->id => $project->name])
            ->toArray();

        $options['new-project'] = 'New Project';

        return [
            Select::make('project')
                ->name('project')
                ->model($this->project)
                ->searchable()
                ->options($options)
                ->searchPrompt('Search...')
                ->extraAttributes(['class' => '-mx-2'])
                ->selectablePlaceholder(false)
                ->live(),
        ];
    }

    public function updatedProject($value): void
    {
        if (! $value) {
            auth()->user()->update(['current_project_id' => null]);

            return;
        }

        if ($value === 'new-project') {
            $this->redirect(route('filament.app.resources.projects.index'));

            return;
        }

        $project = Project::query()->findOrFail($value);
        $this->authorize('view', $project);
        auth()->user()->update(['current_project_id' => $value]);

        $this->redirect('/app');
    }

    public function mount(): void
    {
        $this->project = auth()->user()->current_project_id;
    }
}
