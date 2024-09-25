<?php

namespace App\Web\Clusters\Servers\Resources\Site;

use App\Models\Server;
use App\Models\Site;
use App\Web\Clusters\Servers;
use App\Web\Traits\ResourceHasServersCluster;
use Exception;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SiteResource extends Resource
{
    use ResourceHasServersCluster;

    protected static ?string $model = Site::class;

    protected static ?string $slug = 'sites';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Servers::class;

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Site $site) => Pages\ViewSite::getUrl(['record' => $site]))
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
                //
            ]);
    }

    public static function canCreate(): bool
    {
        ds(static::getServerFromRoute());
        ds(auth()->user()->can('create', [static::getModel(), static::getServerFromRoute()]));

        return auth()->user()->can('create', [static::getModel(), static::getServerFromRoute()]);
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

    //    public static function getGlobalSearchEloquentQuery(): Builder
    //    {
    //        return parent::getGlobalSearchEloquentQuery()->with(['server', 'server.project']);
    //    }
    //
    //    public static function getGloballySearchableAttributes(): array
    //    {
    //        return ['domain', 'server.name', 'server.project.name'];
    //    }
    //
    //    public static function getGlobalSearchResultDetails(Model $record): array
    //    {
    //        $details = [];
    //
    //        $details['Domain'] = $record->domain;
    //        $details['Server'] = $record->server->name;
    //        $details['Project'] = $record->server->project->name;
    //
    //        return $details;
    //    }
    //
    //    public static function getGlobalSearchResultUrl(Model $record): ?string
    //    {
    //        return Pages\ViewSite::getUrl(['record' => $record]);
    //    }
}
