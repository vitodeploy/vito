<?php

namespace App\Web\Resources\Project\Pages;

use App\Actions\Projects\DeleteProject;
use App\Models\Project;
use App\Web\Resources\Project\ProjectResource;
use App\Web\Resources\Project\Widgets\AddUser;
use App\Web\Resources\Project\Widgets\ProjectUsersList;
use App\Web\Resources\Project\Widgets\UpdateProject;
use App\Web\Traits\PageHasWidgets;
use Exception;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ProjectSettings extends Page
{
    use PageHasWidgets;

    protected static string $resource = ProjectResource::class;

    public Project $project;

    public function mount(Project $record): void
    {
        $this->project = $record;
    }

    public function getWidgets(): array
    {
        return [
            [
                UpdateProject::class,
                ['project' => $this->project],
            ],
            [
                AddUser::class,
                ['project' => $this->project],
            ],
            [
                ProjectUsersList::class,
                ['project' => $this->project],
            ],
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Project Settings';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->record($this->project)
                ->label('Delete Project')
                ->modalHeading('Delete Project')
                ->modalDescription('Are you sure you want to delete this project? This action will delete all associated data and cannot be undone.')
                ->using(function (Project $record) {
                    try {
                        app(DeleteProject::class)->delete(auth()->user(), $record);

                        $this->redirectRoute('filament.app.resources.projects.index');
                    } catch (Exception $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
