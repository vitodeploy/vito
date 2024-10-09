<?php

namespace App\Web\Pages\Servers\Logs\Traits;

use App\Models\ServerLog;
use App\Web\Pages\Servers\Logs\Index;
use App\Web\Pages\Servers\Logs\RemoteLogs;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

trait Navigation
{
    public function getSecondSubNavigation(): array
    {
        $items = [];

        if (auth()->user()->can('viewAny', [ServerLog::class, $this->server])) {
            $items[] = NavigationItem::make(Index::getNavigationLabel())
                ->icon('heroicon-o-square-3-stack-3d')
                ->isActiveWhen(fn () => request()->routeIs(Index::getRouteName()))
                ->url(Index::getUrl(parameters: ['server' => $this->server]));

            $items[] = NavigationItem::make(RemoteLogs::getNavigationLabel())
                ->icon('heroicon-o-wifi')
                ->isActiveWhen(fn () => request()->routeIs(RemoteLogs::getRouteName()))
                ->url(RemoteLogs::getUrl(parameters: ['server' => $this->server]));
        }

        return [
            NavigationGroup::make()
                ->items($items),
        ];
    }
}
