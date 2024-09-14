<?php

namespace App\Web\Resources\User;

use App\Actions\User\UpdateProjects;
use App\Actions\User\UpdateUser;
use App\Models\Project;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

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
                TextColumn::make('created_at_by_timezone')
                    ->label('Created At')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make('edit')
                    ->using(function ($record, array $data) {
                        app(UpdateUser::class)->update($record, $data);
                    })
                    ->form(function (Form $form, $record) {
                        return $form
                            ->schema([
                                TextInput::make('name')
                                    ->rules(UpdateUser::rules($record)['name']),
                                TextInput::make('email')
                                    ->rules(UpdateUser::rules($record)['email']),
                                Select::make('timezone')
                                    ->searchable()
                                    ->options(
                                        collect(timezone_identifiers_list())
                                            ->mapWithKeys(fn ($timezone) => [$timezone => $timezone])
                                    )
                                    ->rules(UpdateUser::rules($record)['timezone']),
                                Select::make('role')
                                    ->options(
                                        collect(config('core.user_roles'))
                                            ->mapWithKeys(fn ($role) => [$role => $role])
                                    )
                                    ->rules(UpdateUser::rules($record)['role']),
                            ])
                            ->columns(1);
                    })
                    ->modalWidth(MaxWidth::Large),
                Action::make('update-projects')
                    ->label('Projects')
                    ->icon('heroicon-o-rectangle-stack')
                    ->action(function ($record, array $data) {
                        app(UpdateProjects::class)->update($record, $data);
                        Notification::make()->success()->title('Projects Updated')->send();
                    })
                    ->form(function (Form $form, $record) {
                        return $form
                            ->schema([
                                CheckboxList::make('projects')
                                    ->options(Project::query()->pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->default($record->projects->pluck('id')->toArray())
                                    ->rules(UpdateProjects::rules()['projects.*']),
                            ])
                            ->columns(1);
                    })
                    ->modalSubmitActionLabel('Save')
                    ->modalWidth(MaxWidth::Large),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Web\Resources\User\Pages\ListUsers::route('/'),
        ];
    }
}
