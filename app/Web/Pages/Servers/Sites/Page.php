<?php

namespace App\Web\Pages\Servers\Sites;

use App\Models\Site;
use App\Web\Contracts\HasSecondSubNav;
use App\Web\Pages\Servers\Page as BasePage;
use App\Web\Pages\Servers\Sites\Widgets\SiteSummary;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

abstract class Page extends BasePage implements HasSecondSubNav
{
    public Site $site;

    public function getSecondSubNavigation(): array
    {
        $items = [];

        if (View::canAccess()) {
            $items[] = NavigationItem::make(View::getNavigationLabel())
                ->icon('heroicon-o-globe-alt')
                ->isActiveWhen(fn () => request()->routeIs(View::getRouteName()))
                ->url(View::getUrl(parameters: [
                    'server' => $this->server,
                    'site' => $this->site,
                ]));
        }

        if (Pages\SSL\Index::canAccess()) {
            $items[] = NavigationItem::make(Pages\SSL\Index::getNavigationLabel())
                ->icon('heroicon-o-lock-closed')
                ->isActiveWhen(fn () => request()->routeIs(Pages\SSL\Index::getRouteName()))
                ->url(Pages\SSL\Index::getUrl(parameters: [
                    'server' => $this->server,
                    'site' => $this->site,
                ]));
        }

        if (Pages\Queues\Index::canAccess()) {
            $items[] = NavigationItem::make(Pages\Queues\Index::getNavigationLabel())
                ->icon('heroicon-o-queue-list')
                ->isActiveWhen(fn () => request()->routeIs(Pages\Queues\Index::getRouteName()))
                ->url(Pages\Queues\Index::getUrl(parameters: [
                    'server' => $this->server,
                    'site' => $this->site,
                ]));
        }

        return [
            NavigationGroup::make()
                ->items($items),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return array_merge(parent::getHeaderWidgets(), [
            SiteSummary::make(['site' => $this->site]),
        ]);
    }

    protected static function getSiteFromRoute(): ?Site
    {
        $site = request()->route('site');

        if (! $site) {
            $site = Route::getRoutes()->match(Request::create(url()->previous()))->parameter('site');
        }

        if ($site instanceof Site) {
            return $site;
        }

        if ($site) {
            return Site::query()->find($site);
        }

        return null;
    }
}
