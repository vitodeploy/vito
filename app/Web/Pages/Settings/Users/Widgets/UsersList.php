<?php

namespace App\Web\Pages\Settings\Users\Widgets;

use App\Actions\User\UpdateProjects;
use App\Actions\User\UpdateUser;
use App\Models\Project;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;

class UsersList extends Widget
{
    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('email')
                ->searchable()
                ->sortable(),
            TextColumn::make('timezone'),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
                ->searchable()
                ->sortable(),
            TextColumn::make('role'),
        ];
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = auth()->user();

        return $table
            ->heading(null)
            ->query(User::query())
            ->columns($this->getTableColumns())
            ->actions([
                EditAction::make('edit')
                    ->authorize(fn ($record) => $user->can('update', $record))
                    ->using(function ($record, array $data): void {
                        app(UpdateUser::class)->update($record, $data);
                    })
                    ->form(fn (Form $form, $record): Form => $form
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
                                    collect((array) config('core.user_roles'))
                                        ->mapWithKeys(fn ($role) => [$role => $role])
                                )
                                ->rules(UpdateUser::rules($record)['role']),
                        ])
                        ->columns(1))
                    ->modalWidth(MaxWidth::Large),
                Action::make('update-projects')
                    ->label('Projects')
                    ->icon('heroicon-o-rectangle-stack')
                    ->authorize(fn ($record) => $user->can('update', $record))
                    ->form(fn (Form $form, $record): Form => $form
                        ->schema([
                            CheckboxList::make('projects')
                                ->options(Project::query()->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->default($record->projects->pluck('id')->toArray())
                                ->rules(UpdateProjects::rules()['projects.*']),
                        ])
                        ->columns(1))
                    ->action(function ($record, array $data): void {
                        app(UpdateProjects::class)->update($record, $data);
                        Notification::make()
                            ->title('Projects Updated')
                            ->success()
                            ->send();
                    })
                    ->modalSubmitActionLabel('Save')
                    ->modalWidth(MaxWidth::Large),
                DeleteAction::make('delete')
                    ->authorize(fn (User $record) => $user->can('delete', $record)),
            ]);
    }
}
