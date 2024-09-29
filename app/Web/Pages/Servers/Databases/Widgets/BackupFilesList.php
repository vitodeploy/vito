<?php

namespace App\Web\Pages\Servers\Databases\Widgets;

use App\Actions\Database\RestoreBackup;
use App\Models\Backup;
use App\Models\BackupFile;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class BackupFilesList extends Widget
{
    public Backup $backup;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return BackupFile::query()->where('backup_id', $this->backup->id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable(),
            TextColumn::make('created_at')
                ->formatStateUsing(fn (BackupFile $record) => $record->created_at_by_timezone)
                ->sortable(),
            TextColumn::make('restored_to')
                ->searchable(),
            TextColumn::make('restored_at')
                ->formatStateUsing(fn (BackupFile $record) => $record->getDateTimeByTimezone($record->restored_at))
                ->sortable(),
            TextColumn::make('status')
                ->badge()
                ->color(fn ($state) => BackupFile::$statusColors[$state])
                ->sortable(),

        ];
    }

    protected function applyDefaultSortingToTableQuery(Builder $query): Builder
    {
        return $query->latest('created_at');
    }

    public function getTable(): Table
    {
        return $this->table
            ->actions([
                Action::make('restore')
                    ->hiddenLabel()
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Restore Backup')
                    ->tooltip('Restore Backup')
                    ->authorize(fn (BackupFile $record) => auth()->user()->can('update', $record->backup->database))
                    ->form([
                        Select::make('database')
                            ->label('Restore to')
                            ->options($this->backup->server->databases()->pluck('name', 'id'))
                            ->rules(RestoreBackup::rules()['database'])
                            ->native(false),
                    ])
                    ->modalWidth(MaxWidth::Large)
                    ->action(function (BackupFile $record, array $data) {
                        try {
                            app(RestoreBackup::class)->restore($record, $data);

                            Notification::make()
                                ->success()
                                ->title('Backup is being restored')
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();

                            throw $e;
                        }

                        $this->dispatch('$refresh');
                    }),
                Action::make('delete')
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Delete Database')
                    ->color('danger')
                    ->tooltip('Delete')
                    ->authorize(fn (BackupFile $record) => auth()->user()->can('delete', $record))
                    ->requiresConfirmation()
                    ->action(function (BackupFile $record) {
                        try {
                            $record->delete();
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
