<?php

namespace App\Web\Pages\Settings\Projects\Widgets;

use App\Models\Project;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class UpdateProject extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'components.form';

    public Project $project;

    public string $name;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->name = $project->name;
    }

    public function getFormSchema(): array
    {
        return [
            Section::make()
                ->heading('Project Information')
                ->schema([
                    TextInput::make('name')
                        ->name('name')
                        ->label('Name')
                        ->rules(\App\Actions\Projects\UpdateProject::rules($this->project)['name'])
                        ->placeholder('Enter the project name'),
                ])
                ->footerActions([
                    Action::make('save')
                        ->label('Save')
                        ->action(fn () => $this->submit()),
                ]),
        ];
    }

    public function submit(): void
    {
        $this->authorize('update', $this->project);

        $this->validate();

        app(\App\Actions\Projects\UpdateProject::class)
            ->update($this->project, [
                'name' => $this->name,
            ]);

        Notification::make()
            ->title('Project updated successfully!')
            ->success()
            ->send();
    }
}
