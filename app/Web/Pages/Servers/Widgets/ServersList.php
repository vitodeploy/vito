<?php

namespace App\Web\Pages\Servers\Widgets;

use App\Models\Server;
use App\Models\User;
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
    /**
     * @return Builder<Server>
     */
    protected function getTableQuery(): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        return Server::query()->where('project_id', $user->current_project_id);
    }

    protected function getTableColumns(): array
    {
        return [
            IconColumn::make('provider')
                ->icon(fn (Server $record): string => 'icon-'.$record->provider)
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

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = auth()->user();

        return $table
            ->heading(null)
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->recordUrl(fn (Server $record): string => View::getUrl(parameters: ['server' => $record]))
            ->actions([
                Action::make('settings')
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->authorize(fn ($record) => $user->can('update', $record))
                    ->url(fn (Server $record): string => Settings::getUrl(parameters: ['server' => $record])),
            ]);
    }
}
