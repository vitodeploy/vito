<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Models\Server;
use App\Models\Site;
use App\Web\Pages\Servers\Sites\Settings;
use App\Web\Pages\Servers\Sites\View;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as Widget;
use Illuminate\Database\Eloquent\Builder;

class SitesList extends Widget
{
    public Server $server;

    protected function getTableQuery(): Builder
    {
        return Site::query()->where('server_id', $this->server->id);
    }

    protected static ?string $heading = '';

    protected function getTableColumns(): array
    {
        return [
            IconColumn::make('type')
                ->icon(fn (Site $record) => 'icon-'.$record->type)
                ->tooltip(fn (Site $record) => $record->type)
                ->width(24),
            TextColumn::make('domain')
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
            TextColumn::make('created_at')
                ->formatStateUsing(fn (Site $record) => $record->created_at_by_timezone)
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (Site $site) => Site::$statusColors[$site->status])
                ->searchable()
                ->sortable(),
        ];
    }

    public function getTable(): Table
    {
        return $this->table
            ->recordUrl(fn (Site $record) => View::getUrl(parameters: ['server' => $this->server, 'site' => $record]))
            ->actions([
                Action::make('settings')
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->authorize(fn (Site $record) => auth()->user()->can('update', [$record, $this->server]))
                    ->url(fn (Site $record) => Settings::getUrl(parameters: ['server' => $this->server, 'site' => $record])),
            ]);
    }
}
