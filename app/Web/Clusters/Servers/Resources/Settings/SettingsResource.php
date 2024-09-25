<?php

namespace App\Web\Clusters\Servers\Resources\Settings;

use App\Models\Server;
use App\Web\Clusters\Servers;
use App\Web\Traits\ResourceHasServersCluster;
use Filament\Resources\Resource;

class SettingsResource extends Resource
{
    use ResourceHasServersCluster;

    protected static ?string $slug = 'settings';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $cluster = Servers::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $model = Server::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\Settings::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\ServerDetails::class,
            Widgets\UpdateServerInfo::class,
        ];
    }
}
