<?php

namespace App\Web\Clusters\Servers\Resources\Settings;

use App\Web\Clusters\Servers;
use App\Web\Traits\ResourceHasServersCluster;
use Filament\Resources\Resource;

class SettingsResource extends Resource
{
    use ResourceHasServersCluster;

    protected static ?string $slug = 'settings';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 0;

    protected static ?string $cluster = Servers::class;

    protected static bool $shouldRegisterNavigation = true;

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
