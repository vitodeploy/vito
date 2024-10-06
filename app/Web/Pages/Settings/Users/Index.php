<?php

namespace App\Web\Pages\Settings\Users;

use App\Actions\User\CreateUser;
use App\Models\User;
use App\Web\Components\Page;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'users';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Users';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', User::class) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\UsersList::class],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create')
                ->label('Create User')
                ->icon('heroicon-o-plus')
                ->authorize('create', User::class)
                ->action(function (array $data) {
                    $user = app(CreateUser::class)->create($data);
                    $this->dispatch('$refresh');

                    return $user;
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
