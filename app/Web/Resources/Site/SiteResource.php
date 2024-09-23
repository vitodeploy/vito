<?php

namespace App\Web\Resources\Site;

use App\Models\Server;
use App\Models\Site;
use Exception;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $slug = 'sites';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 2;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
            ])
            ->filters([
                SelectFilter::make('server_id')
                    ->options(function () {
                        return Server::query()
                            ->where('project_id', auth()->user()->current_project_id)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->default(session()->get('current_server_id') ?? null)
                    ->label('Server'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'view' => Pages\ViewSite::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereRelation('server', 'project_id', auth()->user()->current_project_id);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['server', 'server.project']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['domain', 'server.name', 'server.project.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        $details['Domain'] = $record->domain;
        $details['Server'] = $record->server->name;
        $details['Project'] = $record->server->project->name;

        return $details;
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return Pages\ViewSite::getUrl(['record' => $record]);
    }
}
