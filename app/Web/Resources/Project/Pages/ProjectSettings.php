<?php

namespace App\Web\Resources\Project\Pages;

use App\Actions\Projects\DeleteProject;
use App\Models\Project;
use App\Web\Resources\Project\ProjectResource;
use Exception;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ProjectSettings extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'web.project.pages.project-settings';

    public function getTitle(): string|Htmlable
    {
        return 'Project Settings';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Delete Project')
                ->modalHeading('Delete Project')
                ->modalDescription('Are you sure you want to delete this project? This action will delete all associated data and cannot be undone.')
                ->using(function (Project $record) {
                    try {
                        app(DeleteProject::class)->delete(auth()->user(), $record);

                        $this->redirectRoute('filament.app.resources.projects.index');
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Cannot delete project')
                            ->title($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
