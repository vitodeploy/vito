<?php

namespace App\Web\Resources\Server;

use App\Models\Server;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $slug = 'servers';

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 1;

    public static function getWidgets(): array
    {
        return [
            Widgets\SelectServer::class,
            Widgets\ServerSummary::class,
            Widgets\UpdateServerInfo::class,
            Widgets\ServerDetails::class,
            Widgets\InstallingServer::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'view' => Pages\ViewServer::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('project_id', auth()->user()->current_project_id);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['project']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'project.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        $details['Name'] = $record->name;
        $details['Project'] = $record->project->name;

        return $details;
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return Pages\ViewServer::getUrl(['record' => $record]);
    }
}
