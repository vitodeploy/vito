<?php

namespace App\Web\Pages\Servers\Databases\Widgets;

use App\Actions\Database\DeleteDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Models\DatabaseUser;
use App\Models\Server;
use Exception;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class DatabaseUsersList extends Widget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return DatabaseUser::query()->where('server_id', $this->server->id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('username')
                ->searchable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (DatabaseUser $databaseUser) => DatabaseUser::$statusColors[$databaseUser->status])
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->actions([
                $this->passwordAction(),
                $this->linkAction(),
                $this->deleteAction(),
            ]);
    }

    private function passwordAction(): Action
    {
        return Action::make('password')
            ->hiddenLabel()
            ->icon('heroicon-o-key')
            ->color('gray')
            ->modalHeading('Database user\'s password')
            ->modalWidth(MaxWidth::Large)
            ->tooltip('Show the password')
            ->authorize(fn ($record) => auth()->user()->can('view', $record))
            ->form([
                TextInput::make('password')
                    ->label('Password')
                    ->default(fn (DatabaseUser $record) => $record->password)
                    ->disabled(),
            ])
            ->action(function (DatabaseUser $record, array $data) {
                //
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    private function linkAction(): Action
    {
        return Action::make('link')
            ->hiddenLabel()
            ->icon('heroicon-o-link')
            ->modalHeading('Link user to databases')
            ->modalWidth(MaxWidth::Large)
            ->tooltip('Link user')
            ->modalSubmitActionLabel('Save')
            ->authorize(fn ($record) => auth()->user()->can('update', $record))
            ->form([
                CheckboxList::make('databases')
                    ->label('Databases')
                    ->options($this->server->databases()->pluck('name', 'name')->toArray())
                    ->rules(fn (callable $get) => LinkUser::rules($this->server, $get()))
                    ->default(fn (DatabaseUser $record) => $record->databases),
            ])
            ->action(function (DatabaseUser $record, array $data) {
                try {
                    app(LinkUser::class)->link($record, $data);

                    Notification::make()
                        ->success()
                        ->title('User linked to databases!')
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title($e->getMessage())
                        ->send();

                    throw $e;
                }
            });
    }

    private function deleteAction(): Action
    {
        return Action::make('delete')
            ->hiddenLabel()
            ->icon('heroicon-o-trash')
            ->modalHeading('Delete Database User')
            ->color('danger')
            ->tooltip('Delete')
            ->authorize(fn ($record) => auth()->user()->can('delete', $record))
            ->requiresConfirmation()
            ->action(function (DatabaseUser $record) {
                try {
                    app(DeleteDatabaseUser::class)->delete($this->server, $record);
                } catch (Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title($e->getMessage())
                        ->send();

                    throw $e;
                }

                $this->dispatch('$refresh');
            });
    }
}
