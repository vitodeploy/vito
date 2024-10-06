<?php

namespace App\Web\Pages\Servers\Databases\Widgets;

use App\Actions\Database\DeleteDatabase;
use App\Models\Database;
use App\Models\Server;
use Exception;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class DatabasesList extends Widget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Database::query()->where('server_id', $this->server->id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Database $database) => Database::$statusColors[$database->status])
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
                Action::make('delete')
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Delete Database')
                    ->color('danger')
                    ->tooltip('Delete')
                    ->authorize(fn ($record) => auth()->user()->can('delete', $record))
                    ->requiresConfirmation()
                    ->action(function (Database $record) {
                        try {
                            app(DeleteDatabase::class)->delete($this->server, $record);
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();

                            throw $e;
                        }

                        $this->dispatch('$refresh');
                    }),
            ]);
    }
}
