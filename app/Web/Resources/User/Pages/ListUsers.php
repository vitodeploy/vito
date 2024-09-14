<?php

namespace App\Web\Resources\User\Pages;

use App\Actions\User\CreateUser;
use App\Web\Resources\User\UserResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create User')
                ->using(function (array $data) {
                    return app(CreateUser::class)->create($data);
                })
                ->form(function (Form $form) {
                    $rules = CreateUser::rules();

                    return $form
                        ->schema([
                            TextInput::make('name')
                                ->rules($rules['name']),
                            TextInput::make('email')
                                ->rules($rules['email']),
                            TextInput::make('password')
                                ->rules($rules['password']),
                            Select::make('role')
                                ->rules($rules['role'])
                                ->options(collect(config('core.user_roles'))->mapWithKeys(fn ($role) => [$role => $role])),
                        ])
                        ->columns(1);
                })
                ->modalWidth(MaxWidth::Large),
        ];
    }
}
