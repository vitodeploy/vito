<?php

namespace App\Web\Pages\Servers\Logs\Widgets;

use App\Models\Server;
use App\Models\ServerLog;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;

class LogsList extends Widget
{
    public Server $server;

    protected function getTableQuery(): Builder
    {
        return ServerLog::query()->where('server_id', $this->server->id);
    }

    protected static ?string $heading = 'Logs';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at_by_timezone')
                ->label('Created At')
                ->sortable(),
        ];
    }

    protected function applyDefaultSortingToTableQuery(Builder $query): Builder
    {
        return $query->latest('created_at');
    }

    /**
     * @throws Exception
     */
    public function getTable(): Table
    {
        return $this->table
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->authorize(fn ($record) => auth()->user()->can('view', $record))
                    ->modalHeading('View Log')
                    ->modalContent(function (ServerLog $record) {
                        return view('components.console-view', [
                            'slot' => $record->getContent(),
                            'attributes' => new ComponentAttributeBag,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('download')
                    ->label('Download')
                    ->color('secondary')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->authorize(fn ($record) => auth()->user()->can('view', $record))
                    ->action(fn (ServerLog $record) => $record->download()),
            ]);
    }
}
