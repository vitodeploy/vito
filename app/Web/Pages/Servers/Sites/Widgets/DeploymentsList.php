<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Models\Deployment;
use App\Models\Site;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;

class DeploymentsList extends Widget
{
    public Site $site;

    protected $listeners = ['$refresh'];

    protected function getTableQuery(): Builder
    {
        return Deployment::query()->where('site_id', $this->site->id);
    }

    protected function applySortingToTableQuery(Builder $query): Builder
    {
        return $query->latest('created_at');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('commit_data')
                ->label('Commit')
                ->url(fn (Deployment $record) => $record->commit_data['url'] ?? '#')
                ->openUrlInNewTab()
                ->formatStateUsing(fn (Deployment $record) => $record->commit_data['message'] ?? 'No message')
                ->tooltip(fn (Deployment $record) => $record->commit_data['message'] ?? 'No message')
                ->limit(50)
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->formatStateUsing(fn (Deployment $record) => $record->created_at_by_timezone)
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Deployment $record) => Deployment::$statusColors[$record->status])
                ->searchable()
                ->sortable(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->heading('Deployments')
            ->actions([
                Action::make('view')
                    ->hiddenLabel()
                    ->tooltip('View')
                    ->icon('heroicon-o-eye')
                    ->authorize(fn ($record) => auth()->user()->can('view', $record->log))
                    ->modalHeading('View Log')
                    ->modalContent(function (Deployment $record) {
                        return view('components.console-view', [
                            'slot' => $record->log?->getContent(),
                            'attributes' => new ComponentAttributeBag,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('download')
                    ->hiddenLabel()
                    ->tooltip('Download')
                    ->color('gray')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->authorize(fn ($record) => auth()->user()->can('view', $record->log))
                    ->action(fn (Deployment $record) => $record->log?->download()),
            ]);
    }
}
