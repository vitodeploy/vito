<?php

namespace App\Web\Pages\Servers\Widgets;

use App\Models\Server;
use App\Web\Pages\Servers\View;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class ServersList extends Widget
{
    protected function getTableQuery(): Builder
    {
        return Server::query()->where('project_id', auth()->user()->current_project_id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')
                ->searchable()
                ->sortable(),
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            ViewColumn::make('tags.name')
                ->label('Tags')
                ->view('web.components.tags')
                ->extraCellAttributes(['class' => 'px-3'])
                ->searchable()
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Server $server) => Server::$statusColors[$server->status])
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at_by_timezone')
                ->label('Created At')
                ->searchable()
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->recordUrl(fn (Server $record) => View::getUrl(parameters: ['server' => $record]))
            ->actions([
                Action::make('settings')
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->authorize(fn ($record) => auth()->user()->can('update', $record))
                    ->url(fn (Server $record) => '/'),
            ]);
    }
}
