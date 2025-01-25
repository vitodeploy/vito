<?php

namespace App\Web\Pages\Servers\Databases\Widgets;

use App\Actions\Database\ManageBackup;
use App\Actions\Database\RunBackup;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Server;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                Action::make('edit')
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Edit Configuration')
                    ->disabled(fn (Backup $record) => ! in_array($record->status, ['running', 'failed']))
                    ->authorize(fn (Backup $record) => auth()->user()->can('update', $record))
                    ->modelLabel('Edit Backup')
                    ->modalWidth(MaxWidth::Large)
                    ->modalSubmitActionLabel('Update')
                    ->form([
                        Select::make('interval')
                            ->label('Interval')
                            ->options(config('core.cronjob_intervals'))
                            ->reactive()
                            ->default(fn (Backup $record) => $record->isCustomInterval() ? 'custom' : $record->interval)
                            ->rules(fn (callable $get) => ManageBackup::rules($this->server, $get())['interval']),
                        TextInput::make('custom_interval')
                            ->label('Custom Interval (Cron)')
                            ->rules(fn (callable $get) => ManageBackup::rules($this->server, $get())['custom_interval'])
                            ->visible(fn (callable $get) => $get('interval') === 'custom')
                            ->default(fn (Backup $record) => $record->isCustomInterval() ? $record->interval : '')
                            ->placeholder('0 * * * *'),
                        TextInput::make('keep')
                            ->label('Backups to Keep')
                            ->default(fn (Backup $record) => $record->keep_backups)
                            ->rules(fn (callable $get) => ManageBackup::rules($this->server, $get())['keep'])
                            ->helperText('How many backups to keep before deleting the oldest one'),
                    ])
                    ->action(function (Backup $backup, array $data) {
                        run_action($this, function () use ($data, $backup) {
                            app(ManageBackup::class)->update($backup, $data);

                            $this->dispatch('$refresh');

                            Notification::make()
                                ->success()
                                ->title('Backup updated!')
                                ->send();
                        });
                    }),
                Action::make('files')
                    ->hiddenLabel()
                    ->icon('heroicon-o-rectangle-stack')
                    ->modalHeading('Backup Files')
                    ->color('gray')
                    ->tooltip('Show backup files')
                    ->disabled(fn (Backup $record) => ! in_array($record->status, ['running', 'failed']))
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
                    ->modalHeading('Delete Backup & Files')
                    ->disabled(fn (Backup $record) => ! in_array($record->status, ['running', 'failed']))
                    ->color('danger')
                    ->tooltip('Delete')
                    ->authorize(fn (Backup $record) => auth()->user()->can('delete', $record))
                    ->requiresConfirmation()
                    ->action(function (Backup $record) {
                        app(ManageBackup::class)->delete($record);

                        $this->dispatch('$refresh');
                    }),
            ]);
    }
}
