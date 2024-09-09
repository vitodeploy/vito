<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Actions\Projects\DeleteProject;
use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Exception;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ProjectSettings extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.project-settings';

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
