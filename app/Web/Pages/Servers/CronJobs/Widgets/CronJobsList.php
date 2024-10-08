<?php

namespace App\Web\Pages\Servers\CronJobs\Widgets;

use App\Actions\CronJob\DeleteCronJob;
use App\Actions\CronJob\DisableCronJob;
use App\Actions\CronJob\EnableCronJob;
use App\Models\CronJob;
use App\Models\Server;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class CronJobsList extends Widget
{
    public Server $server;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return CronJob::query()->where('server_id', $this->server->id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('command')
                ->limit(40)
                ->tooltip(fn (CronJob $cronJob) => $cronJob->command)
                ->searchable()
                ->copyable(),
            TextColumn::make('frequency')
                ->formatStateUsing(fn (CronJob $cronJob) => $cronJob->frequencyLabel())
                ->searchable()
                ->sortable()
                ->copyable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (CronJob $record) => CronJob::$statusColors[$record->status])
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->formatStateUsing(fn (CronJob $cronJob) => $cronJob->created_at_by_timezone)
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->actions([
                Action::make('enable')
                    ->hiddenLabel()
                    ->tooltip('Enable')
                    ->icon('heroicon-o-play')
                    ->requiresConfirmation()
                    ->authorize(fn (CronJob $record) => auth()->user()->can('update', [$record, $this->server]))
                    ->visible(fn (CronJob $record) => $record->isDisabled())
                    ->action(function (CronJob $record) {
                        run_action($this, function () use ($record) {
                            app(EnableCronJob::class)->enable($this->server, $record);
                        });
                    }),
                Action::make('disable')
                    ->hiddenLabel()
                    ->tooltip('Disable')
                    ->icon('heroicon-o-stop')
                    ->requiresConfirmation()
                    ->authorize(fn (CronJob $record) => auth()->user()->can('update', [$record, $this->server]))
                    ->visible(fn (CronJob $record) => $record->isEnabled())
                    ->action(function (CronJob $record) {
                        run_action($this, function () use ($record) {
                            app(DisableCronJob::class)->disable($this->server, $record);
                        });
                    }),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->tooltip('Delete')
                    ->color('danger')
                    ->hiddenLabel()
                    ->requiresConfirmation()
                    ->authorize(fn (CronJob $record) => auth()->user()->can('delete', $record))
                    ->action(function (CronJob $record) {
                        try {
                            app(DeleteCronJob::class)->delete($this->server, $record);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }

                        $this->dispatch('$refresh');
                    }),
            ]);
    }
}
