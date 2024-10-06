<?php

namespace App\Web\Pages\Servers\Widgets;

use App\Models\Server;
use App\Web\Pages\Servers\Settings;
use App\Web\Pages\Servers\View;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
            IconColumn::make('provider')
                ->icon(fn (Server $record) => 'icon-'.$record->provider)
                ->tooltip(fn (Server $record) => $record->provider)
                ->width(24),
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('tags')
                ->label('Tags')
                ->badge()
                ->icon('heroicon-o-tag')
                ->formatStateUsing(fn ($state) => $state->name)
                ->color(fn ($state) => $state->color)
                ->searchable()
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Server $server) => Server::$statusColors[$server->status])
                ->searchable()
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($record) => $record->created_at_by_timezone)
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
                    ->url(fn (Server $record) => Settings::getUrl(parameters: ['server' => $record])),
            ]);
    }
}
