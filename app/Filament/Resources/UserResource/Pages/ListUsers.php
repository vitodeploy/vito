<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Actions\User\CreateUser;
use App\Filament\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create Project')
                ->using(function (array $data) {
                    try {
                        return app(CreateUser::class)->create($data);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title($e->getMessage())
                            ->send();

                        return null;
                    }
                })
                ->form(function (Form $form) {
                    return $form
                        ->schema([
                            TextInput::make('name')
                                ->rules(CreateUser::rules()['name']),
                            TextInput::make('email')
                                ->rules(CreateUser::rules()['email']),
                            TextInput::make('password')
                                ->rules(CreateUser::rules()['password']),
                            TextInput::make('role')
                                ->rules(CreateUser::rules()['role']),
                        ])
                        ->columns(1);
                })
                ->createAnother(false)
                ->modalWidth(MaxWidth::Large),
        ];
    }
}
