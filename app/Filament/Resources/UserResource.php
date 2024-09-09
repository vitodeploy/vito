<?php

namespace App\Filament\Resources;

use App\Actions\User\CreateUser;
use App\Actions\User\UpdateUser;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('timezone'),
                TextColumn::make('role'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->using(function ($record, array $data) {
                        try {
                            app(UpdateUser::class)->update($record, $data);
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }
                    })
                    ->form(function (Form $form, $record) {
                        return $form
                            ->schema([
                                TextInput::make('name')
                                    ->rules(UpdateUser::rules($record)['name']),
                                TextInput::make('email')
                                    ->rules(UpdateUser::rules($record)['email']),
                                TextInput::make('timezone')
                                    ->rules(UpdateUser::rules($record)['timezone']),
                                TextInput::make('role')
                                    ->rules(UpdateUser::rules($record)['role']),
                            ])
                            ->columns(1);
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
