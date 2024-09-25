<?php

namespace App\Web\Clusters\Servers\Resources\Overview;

use App\Models\Server;
use App\Web\Clusters\Servers;
use App\Web\Traits\ResourceHasServersCluster;
use Filament\Resources\Resource;

class OverviewResource extends Resource
{
    use ResourceHasServersCluster;

    protected static ?string $slug = 'overview';

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?int $navigationSort = 0;

    protected static ?string $cluster = Servers::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $model = Server::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\Overview::route('/'),
        ];
    }
}
