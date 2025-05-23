<?php

namespace App\Web\Pages\Scripts\Widgets;

use App\Models\Script;
use App\Models\ScriptExecution;
use App\Web\Pages\Servers\View;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;

class ScriptExecutionsList extends Widget
{
    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    public Script $script;

    /**
     * @return Builder<ScriptExecution>
     */
    protected function getTableQuery(): Builder
    {
        return ScriptExecution::query()->where('script_id', $this->script->id);
    }

    /**
     * @param  Builder<ScriptExecution>  $query
     * @return Builder<ScriptExecution>
     */
    protected function applyDefaultSortingToTableQuery(Builder $query): Builder
    {
        return $query->latest('created_at');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('server')
                ->formatStateUsing(fn (ScriptExecution $record) => $record->getServer()->name ?? 'Unknown')
                ->url(function (ScriptExecution $record): ?string {
                    $server = $record->getServer();

                    return $server instanceof \App\Models\Server ? View::getUrl(['server' => $server]) : null;
                })
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Executed At')
                ->formatStateUsing(fn (ScriptExecution $record) => $record->created_at_by_timezone)
                ->searchable()
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (ScriptExecution $record) => ScriptExecution::$statusColors[$record->status])
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
                Action::make('logs')
                    ->hiddenLabel()
                    ->tooltip('Logs')
                    ->icon('heroicon-o-eye')
                    ->authorize(fn (ScriptExecution $record) => auth()->user()?->can('view', $record->serverLog))
                    ->modalHeading('View Log')
                    ->modalContent(fn (ScriptExecution $record) => view('components.console-view', [
                        'slot' => $record->serverLog?->getContent(),
                        'attributes' => new ComponentAttributeBag,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ]);
    }
}
