<?php

namespace App\Web\Pages\Settings\Projects;

use App\Actions\Projects\CreateProject;
use App\Models\Project;
use App\Web\Components\Page;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/projects';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Projects';

    public static function getNavigationItemActiveRoutePattern(): string
    {
        return static::getRouteName().'*';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Project::class) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\ProjectsList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create Project')
                ->icon('heroicon-o-plus')
                ->authorize('create', Project::class)
                ->using(function (array $data) {
                    return app(CreateProject::class)->create(auth()->user(), $data);
                })
                ->form(function (Form $form) {
                    return $form->schema([
                        TextInput::make('name')
                            ->name('name')
                            ->rules(CreateProject::rules()['name']),
                    ])->columns(1);
                })
                ->createAnother(false)
                ->modalWidth(MaxWidth::Large),
        ];
    }
}
