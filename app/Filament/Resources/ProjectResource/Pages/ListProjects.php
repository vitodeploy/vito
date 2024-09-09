<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Actions\Projects\CreateProject;
use App\Filament\Resources\ProjectResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create Project')
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
