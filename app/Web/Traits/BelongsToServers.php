<?php

namespace App\Web\Traits;

use App\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

trait BelongsToServers
{
    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        if (! isset($parameters['server'])) {
            $parameters['server'] = request()->route('server') ?? 0;
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }

    public static function getServerFromRoute(): Server
    {
        $server = request()->route('server');

        if (! $server) {
            $server = Route::getRoutes()->match(Request::create(url()->previous()))->parameter('server');
        }

        if (! $server instanceof Server) {
            $server = Server::query()->find($server);
        }

        if (! $server) {
            $server = new Server;
        }

        return $server;
    }

    public static function canViewAny(): bool
    {
        return static::can('viewAny');

        return auth()->user()->can('viewAny', [static::getModel(), static::getServerFromRoute()]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create', [static::getModel(), static::getServerFromRoute()]);
    }

    public static function authorizeViewAny(): void
    {
        Gate::authorize('viewAny', [static::getModel(), static::getServerFromRoute()]);
    }

    public static function authorizeCreate(): void
    {
        Gate::authorize('create', [static::getModel(), static::getServerFromRoute()]);
    }

    public static function authorizeEdit(Model $record): void
    {
        Gate::authorize('update', [$record, static::getServerFromRoute()]);
    }

    public static function authorizeView(Model $record): void
    {
        Gate::authorize('view', [$record, static::getServerFromRoute()]);
    }
}
