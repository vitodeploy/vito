<?php

namespace App\Web\Pages\Settings\Projects;

use App\Actions\Projects\DeleteProject;
use App\Models\Project;
use App\Web\Components\Page;
use App\Web\Pages\Settings\Projects\Widgets\AddUser;
use App\Web\Pages\Settings\Projects\Widgets\ProjectUsersList;
use App\Web\Pages\Settings\Projects\Widgets\UpdateProject;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

class Settings extends Page
{
    protected static ?string $slug = 'settings/projects/{project}';

    protected static ?string $title = 'Project Settings';

    protected static bool $shouldRegisterNavigation = false;

    public Project $project;

    public function mount(): void
    {
        $this->authorize('update', $this->project);
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
            Action::make('delete')
                ->record($this->project)
                ->color('danger')
                ->label('Delete Project')
                ->icon('heroicon-o-trash')
                ->modalHeading('Delete Project')
                ->modalDescription('Are you sure you want to delete this project? This action will delete all associated data and cannot be undone.')
                ->requiresConfirmation()
                ->action(function (Project $record) {
                    try {
                        app(DeleteProject::class)->delete(auth()->user(), $record);

                        Notification::make()
                            ->success()
                            ->title('Project deleted successfully.')
                            ->send();

                        $this->redirect(Index::getUrl());
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
