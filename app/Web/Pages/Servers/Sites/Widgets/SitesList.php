<?php

namespace App\Web\Pages\Servers\Sites\Widgets;

use App\Models\Server;
use App\Models\Site;
use App\Web\Pages\Servers\View;
use Filament\Tables\Actions\Action;
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
            TextColumn::make('id')
                ->searchable()
                ->sortable(),
            TextColumn::make('server.name')
                ->searchable()
                ->sortable(),
            TextColumn::make('domain')
                ->searchable(),
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
//            ->recordUrl(fn (Server $record) => View::getUrl(parameters: ['server' => $record]))
            ->actions([
                //                Action::make('settings')
                //                    ->label('Settings')
                //                    ->icon('heroicon-o-cog-6-tooth')
                //                    ->authorize(fn ($record) => auth()->user()->can('update', $record))
                //                    ->url(fn (Server $record) => '/'),
            ]);
    }
}
