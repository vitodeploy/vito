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

    protected static string $view = 'web.components.form';

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

        return [
            Select::make('project')
                ->name('project')
                ->model($this->project)
                ->label('Project')
                ->searchable()
                ->options($options)
                ->searchPrompt('Select a project...')
                ->extraAttributes(['class' => '-mx-2 pointer-choices'])
                ->selectablePlaceholder(false)
                ->live(),
        ];
    }

    public function updatedProject($value): void
    {
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
