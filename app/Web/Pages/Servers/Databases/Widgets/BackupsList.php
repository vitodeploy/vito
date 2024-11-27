<?php

namespace App\Web\Pages\Servers\Databases\Widgets;

use App\Actions\Database\RunBackup;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Server;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class BackupsList extends Widget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Backup::query()->where('server_id', $this->server->id);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('database.name')
                ->label('Database')
                ->searchable(),
            TextColumn::make('storage.profile')
                ->label('Storage')
                ->searchable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Backup $backup) => Backup::$statusColors[$backup->status])
                ->sortable(),
            TextColumn::make('lastFile.status')
                ->label('Last file status')
                ->badge()
                ->color(fn ($state) => BackupFile::$statusColors[$state])
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
                ->sortable(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                Action::make('files')
                    ->hiddenLabel()
                    ->icon('heroicon-o-rectangle-stack')
                    ->modalHeading('Backup Files')
                    ->color('gray')
                    ->tooltip('Show backup files')
                    ->authorize(fn (Backup $record) => auth()->user()->can('viewAny', [BackupFile::class, $record]))
                    ->modalContent(fn (Backup $record) => view('components.dynamic-widget', [
                        'widget' => BackupFilesList::class,
                        'params' => [
                            'backup' => $record,
                        ],
                    ]))
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalFooterActions([
                        Action::make('backup')
                            ->label('Run Backup')
                            ->icon('heroicon-o-play')
                            ->color('primary')
                            ->action(function (Backup $record) {
                                app(RunBackup::class)->run($record);

                                $this->dispatch('$refresh');
                            }),
                    ]),
                Action::make('delete')
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Delete Database')
                    ->color('danger')
                    ->tooltip('Delete')
                    ->authorize(fn (Backup $record) => auth()->user()->can('delete', $record))
                    ->requiresConfirmation()
                    ->action(function (Backup $record) {
                        run_action($this, function () use ($record) {
                            $record->delete();
                            $this->dispatch('$refresh');
                        });
                    }),
            ]);
    }
}
