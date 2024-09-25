<?php

namespace App\Web\Clusters;

use App\Models\Server;
use Filament\Clusters\Cluster;
use Illuminate\Database\Eloquent\Model;

class Servers extends Cluster
{
    protected static ?string $slug = 'servers/{server}';

    protected static bool $shouldRegisterNavigation = false;

    protected $listeners = ['$refresh'];

    /*
     * A hack to make the servers on the navigation active when user is on a server page
     */
    public static function prependClusterRouteBaseName(string $name): string
    {
        return 'resources.'.parent::prependClusterRouteBaseName($name);
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        $parameters['server'] = request()->route('server') ?? 0;

        return parent::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }
}
