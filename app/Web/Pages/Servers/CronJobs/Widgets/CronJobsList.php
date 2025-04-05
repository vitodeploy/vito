<?php

namespace App\Web\Pages\Servers\CronJobs\Widgets;

use App\Actions\CronJob\DeleteCronJob;
use App\Actions\CronJob\DisableCronJob;
use App\Actions\CronJob\EnableCronJob;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class CronJobsList extends Widget
{
    public Server $server;

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    /**
     * @return Builder<CronJob>
     */
    protected function getTableQuery(): Builder
    {
        return CronJob::query()->where('server_id', $this->server->id);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('command')
                ->limit(40)
                ->tooltip(fn (CronJob $cronJob) => $cronJob->command)
                ->searchable()
                ->copyable(),
            TextColumn::make('frequency')
                ->formatStateUsing(fn (CronJob $cronJob): string => $cronJob->frequencyLabel())
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

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = auth()->user();

        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([
                Action::make('enable')
                    ->hiddenLabel()
                    ->tooltip('Enable')
                    ->icon('heroicon-o-play')
                    ->requiresConfirmation()
                    ->authorize(fn (CronJob $record) => $user->can('update', [$record, $this->server]))
                    ->visible(fn (CronJob $record): bool => $record->isDisabled())
                    ->action(function (CronJob $record): void {
                        run_action($this, function () use ($record): void {
                            app(EnableCronJob::class)->enable($this->server, $record);
                        });
                    }),
                Action::make('disable')
                    ->hiddenLabel()
                    ->tooltip('Disable')
                    ->icon('heroicon-o-stop')
                    ->requiresConfirmation()
                    ->authorize(fn (CronJob $record) => $user->can('update', [$record, $this->server]))
                    ->visible(fn (CronJob $record): bool => $record->isEnabled())
                    ->action(function (CronJob $record): void {
                        run_action($this, function () use ($record): void {
                            app(DisableCronJob::class)->disable($this->server, $record);
                        });
                    }),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->tooltip('Delete')
                    ->color('danger')
                    ->hiddenLabel()
                    ->requiresConfirmation()
                    ->authorize(fn (CronJob $record) => $user->can('delete', $record))
                    ->action(function (CronJob $record): void {
                        try {
                            app(DeleteCronJob::class)->delete($this->server, $record);
                        } catch (Exception $e) {
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
