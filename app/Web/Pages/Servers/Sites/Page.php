<?php

namespace App\Web\Pages\Servers\Sites;

use App\Models\Redirect;
use App\Models\ServerLog;
use App\Models\Site;
use App\Models\Ssl;
use App\Models\User;
use App\Models\Worker;
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
        /** @var User $user */
        $user = auth()->user();
        $items = [];

        if ($user->can('view', [$this->site, $this->server])) {
            $items[] = NavigationItem::make(View::getNavigationLabel())
                ->icon('icon-'.$this->site->type)
                ->isActiveWhen(fn () => request()->routeIs(View::getRouteName()))
                ->url(View::getUrl(parameters: [
                    'server' => $this->server,
                    'site' => $this->site,
                ]));
        }

        if ($user->can('viewAny', [Ssl::class, $this->site, $this->server])) {
            $items[] = NavigationItem::make(Pages\SSL\Index::getNavigationLabel())
                ->icon('heroicon-o-lock-closed')
                ->isActiveWhen(fn () => request()->routeIs(Pages\SSL\Index::getRouteName()))
                ->url(Pages\SSL\Index::getUrl(parameters: [
                    'server' => $this->server,
                    'site' => $this->site,
                ]));
        }

        if ($user->can('viewAny', [Worker::class, $this->server, $this->site])) {
            $items[] = NavigationItem::make(Pages\Workers\Index::getNavigationLabel())
                ->icon('heroicon-o-queue-list')
                ->isActiveWhen(fn () => request()->routeIs(Pages\Workers\Index::getRouteName()))
                ->url(Pages\Workers\Index::getUrl(parameters: [
                    'server' => $this->server,
                    'site' => $this->site,
                ]));
        }

        if ($user->can('viewAny', [ServerLog::class, $this->server])) {
            $items[] = NavigationItem::make(Pages\Logs\Index::getNavigationLabel())
                ->icon('heroicon-o-square-3-stack-3d')
                ->isActiveWhen(fn () => request()->routeIs(Pages\Logs\Index::getRouteName()))
                ->url(Pages\Logs\Index::getUrl(parameters: [
                    'server' => $this->server,
                    'site' => $this->site,
                ]));
        }

        if ($user->can('update', [$this->site, $this->server])) {
            $items[] = NavigationItem::make(Settings::getNavigationLabel())
                ->icon('heroicon-o-wrench-screwdriver')
                ->isActiveWhen(fn () => request()->routeIs(Settings::getRouteName()))
                ->url(Settings::getUrl(parameters: [
                    'server' => $this->server,
                    'site' => $this->site,
                ]));
        }

        if ($user->can('view', [Redirect::class, $this->site, $this->server])) {
            $items[] = NavigationItem::make(Pages\Redirects\Index::getNavigationLabel())
                ->icon('heroicon-o-arrows-right-left')
                ->isActiveWhen(fn () => request()->routeIs(Pages\Redirects\Index::getRouteName()))
                ->url(Pages\Redirects\Index::getUrl(parameters: [
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
            /** @var Site $site */
            $site = Site::query()->findOrFail($site);

            return $site;
        }

        return null;
    }
}
