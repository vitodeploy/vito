<?php

namespace App\Web\Pages\Servers\Databases\Widgets;

use App\Actions\Database\ManageBackupFile;
use App\Actions\Database\RestoreBackup;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
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

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    /**
     * @return Builder<BackupFile>
     */
    protected function getTableQuery(): Builder
    {
        return BackupFile::query()->where('backup_id', $this->backup->id);
    }

    /**
     * @return array<int, mixed>
     */
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
                ->formatStateUsing(fn (BackupFile $record): string => $record->getDateTimeByTimezone($record->restored_at))
                ->sortable(),
            TextColumn::make('status')
                ->badge()
                ->color(fn ($state) => BackupFile::$statusColors[$state])
                ->sortable(),

        ];
    }

    /**
     * @param  Builder<BackupFile>  $query
     * @return Builder<BackupFile>
     */
    protected function applyDefaultSortingToTableQuery(Builder $query): Builder
    {
        return $query->latest('created_at');
    }

    public function table(Table $table): Table
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                Action::make('download')
                    ->hiddenLabel()
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (BackupFile $record): bool => $record->isAvailable() && $record->isLocal())
                    ->tooltip('Download')
                    ->action(fn (BackupFile $record) => app(ManageBackupFile::class)->download($record))
                    ->authorize(fn (BackupFile $record) => $user->can('view', $record)),
                Action::make('restore')
                    ->hiddenLabel()
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Restore Backup')
                    ->tooltip('Restore Backup')
                    ->disabled(fn (BackupFile $record): bool => ! $record->isAvailable())
                    ->authorize(fn (BackupFile $record) => $user->can('update', $record->backup))
                    ->form([
                        Select::make('database')
                            ->label('Restore to')
                            ->options($this->backup->server->databases()->pluck('name', 'id'))
                            ->rules(RestoreBackup::rules()['database'])
                            ->native(false),
                    ])
                    ->modalWidth(MaxWidth::Large)
                    ->action(function (BackupFile $record, array $data): void {
                        run_action($this, function () use ($record, $data): void {
                            $this->validate();

                            /** @var Database $database */
                            $database = Database::query()->findOrFail($data['database']);

                            $this->authorize('update', $database);

                            app(RestoreBackup::class)->restore($record, $data);

                            Notification::make()
                                ->success()
                                ->title('Backup is being restored')
                                ->send();

                            $this->dispatch('$refresh');
                        });
                    }),
                Action::make('delete')
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Delete Backup File')
                    ->color('danger')
                    ->disabled(fn (BackupFile $record): bool => ! $record->isAvailable())
                    ->tooltip('Delete')
                    ->authorize(fn (BackupFile $record) => $user->can('delete', $record))
                    ->requiresConfirmation()
                    ->action(function (BackupFile $record): void {
                        app(ManageBackupFile::class)->delete($record);
                        $this->dispatch('$refresh');
                    }),
            ]);
    }
}
