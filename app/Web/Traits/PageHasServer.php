<?php

namespace App\Web\Traits;

use App\Models\Site;
use App\Web\Pages\Servers\Settings;
use App\Web\Pages\Servers\Sites\Index;
use App\Web\Pages\Servers\View;
use App\Web\Pages\Servers\Widgets\ServerSummary;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\Support\Htmlable;

trait PageHasServer
{
    public function getTitle(): string|Htmlable
    {
        return static::$title.' - '.$this->server->name;
    }

    public function getSubNavigation(): array
    {
        $items = [];

        if (auth()->user()?->can('view', $this->server)) {
            $items[] = NavigationItem::make('Overview')
                ->icon('heroicon-o-chart-pie')
                ->isActiveWhen(fn () => request()->routeIs(View::getRouteName()))
                ->url(View::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()?->can('viewAny', [Site::class, $this->server])) {
            $items[] = NavigationItem::make('Sites')
                ->icon('heroicon-o-globe-alt')
                ->isActiveWhen(fn () => request()->routeIs(Index::getRouteName().'*'))
                ->url(Index::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()?->can('update', $this->server)) {
            $items[] = NavigationItem::make('Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->isActiveWhen(fn () => request()->routeIs(Settings::getRouteName().'*'))
                ->url(Settings::getUrl(parameters: ['server' => $this->server]));
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

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 1;
    }
}
