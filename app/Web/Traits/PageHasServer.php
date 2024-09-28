<?php

namespace App\Web\Traits;

use App\Models\Server;
use App\Web\Pages\Servers\Logs\Index as LogsIndex;
use App\Web\Pages\Servers\PHP\Index as PHPIndex;
use App\Web\Pages\Servers\Settings as ServerSettings;
use App\Web\Pages\Servers\Sites\Index as SitesIndex;
use App\Web\Pages\Servers\View as ServerView;
use App\Web\Pages\Servers\Widgets\ServerSummary;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

trait PageHasServer
{
    public function getTitle(): string|Htmlable
    {
        return static::$title.' - '.$this->server->name;
    }

    public function getSubNavigation(): array
    {
        $items = [];

        if (ServerView::canAccess()) {
            $items[] = NavigationItem::make('Overview')
                ->icon('heroicon-o-chart-pie')
                ->isActiveWhen(fn () => request()->routeIs(ServerView::getRouteName()))
                ->url(ServerView::getUrl(parameters: ['server' => $this->server]));
        }

        if (SitesIndex::canAccess()) {
            $items[] = NavigationItem::make('Sites')
                ->icon('heroicon-o-globe-alt')
                ->isActiveWhen(fn () => request()->routeIs(SitesIndex::getRouteName().'*'))
                ->url(SitesIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (PHPIndex::canAccess()) {
            $items[] = NavigationItem::make('PHP')
                ->icon('heroicon-o-code-bracket')
                ->isActiveWhen(fn () => request()->routeIs(PHPIndex::getRouteName().'*'))
                ->url(PHPIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (LogsIndex::canAccess()) {
            $items[] = NavigationItem::make('Logs')
                ->icon('heroicon-o-square-3-stack-3d')
                ->isActiveWhen(fn () => request()->routeIs(LogsIndex::getRouteName().'*'))
                ->url(LogsIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (ServerSettings::canAccess()) {
            $items[] = NavigationItem::make('Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->isActiveWhen(fn () => request()->routeIs(ServerSettings::getRouteName().'*'))
                ->url(ServerSettings::getUrl(parameters: ['server' => $this->server]));
        }

        return $items;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ServerSummary::make([
                'server' => $this->server,
            ]),
        ];
    }

    protected static function getServerFromRoute(): ?Server
    {
        $server = request()->route('server');

        if (! $server) {
            $server = Route::getRoutes()->match(Request::create(url()->previous()))->parameter('server');
        }

        if ($server instanceof Server) {
            return $server;
        }

        if ($server) {
            return Server::query()->find($server);
        }

        return null;
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 1;
    }
}
