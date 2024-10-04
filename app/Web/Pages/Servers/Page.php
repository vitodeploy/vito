<?php

namespace App\Web\Pages\Servers;

use App\Models\Server;
use App\Web\Components\Page as BasePage;
use App\Web\Pages\Servers\Console\Index as ConsoleIndex;
use App\Web\Pages\Servers\CronJobs\Index as CronJobsIndex;
use App\Web\Pages\Servers\Databases\Index as DatabasesIndex;
use App\Web\Pages\Servers\Firewall\Index as FirewallIndex;
use App\Web\Pages\Servers\Logs\Index as LogsIndex;
use App\Web\Pages\Servers\Metrics\Index as MetricsIndex;
use App\Web\Pages\Servers\PHP\Index as PHPIndex;
use App\Web\Pages\Servers\Services\Index as ServicesIndex;
use App\Web\Pages\Servers\Settings as ServerSettings;
use App\Web\Pages\Servers\Sites\Index as SitesIndex;
use App\Web\Pages\Servers\SSHKeys\Index as SshKeysIndex;
use App\Web\Pages\Servers\View as ServerView;
use App\Web\Pages\Servers\Widgets\ServerSummary;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

abstract class Page extends BasePage
{
    public Server $server;

    protected static bool $shouldRegisterNavigation = false;

    public function getSubNavigation(): array
    {
        $items = [];

        if (ServerView::canAccess()) {
            $items[] = NavigationItem::make(ServerView::getNavigationLabel())
                ->icon('heroicon-o-chart-pie')
                ->isActiveWhen(fn () => request()->routeIs(ServerView::getRouteName()))
                ->url(ServerView::getUrl(parameters: ['server' => $this->server]));
        }

        if (SitesIndex::canAccess()) {
            $items[] = NavigationItem::make(SitesIndex::getNavigationLabel())
                ->icon('heroicon-o-cursor-arrow-ripple')
                ->isActiveWhen(fn () => request()->routeIs(SitesIndex::getRouteName().'*'))
                ->url(SitesIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (DatabasesIndex::canAccess()) {
            $items[] = NavigationItem::make(DatabasesIndex::getNavigationLabel())
                ->icon('heroicon-o-circle-stack')
                ->isActiveWhen(fn () => request()->routeIs(DatabasesIndex::getRouteName().'*'))
                ->url(DatabasesIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (PHPIndex::canAccess()) {
            $items[] = NavigationItem::make(PHPIndex::getNavigationLabel())
                ->icon('heroicon-o-code-bracket')
                ->isActiveWhen(fn () => request()->routeIs(PHPIndex::getRouteName().'*'))
                ->url(PHPIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (FirewallIndex::canAccess()) {
            $items[] = NavigationItem::make(FirewallIndex::getNavigationLabel())
                ->icon('heroicon-o-fire')
                ->isActiveWhen(fn () => request()->routeIs(FirewallIndex::getRouteName().'*'))
                ->url(FirewallIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (CronJobsIndex::canAccess()) {
            $items[] = NavigationItem::make(CronJobsIndex::getNavigationLabel())
                ->icon('heroicon-o-clock')
                ->isActiveWhen(fn () => request()->routeIs(CronJobsIndex::getRouteName().'*'))
                ->url(CronJobsIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (SshKeysIndex::canAccess()) {
            $items[] = NavigationItem::make(SshKeysIndex::getNavigationLabel())
                ->icon('heroicon-o-key')
                ->isActiveWhen(fn () => request()->routeIs(SshKeysIndex::getRouteName().'*'))
                ->url(SshKeysIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (ServicesIndex::canAccess()) {
            $items[] = NavigationItem::make(ServicesIndex::getNavigationLabel())
                ->icon('heroicon-o-cog-6-tooth')
                ->isActiveWhen(fn () => request()->routeIs(ServicesIndex::getRouteName().'*'))
                ->url(ServicesIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (MetricsIndex::canAccess()) {
            $items[] = NavigationItem::make(MetricsIndex::getNavigationLabel())
                ->icon('heroicon-o-chart-bar')
                ->isActiveWhen(fn () => request()->routeIs(MetricsIndex::getRouteName().'*'))
                ->url(MetricsIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (ConsoleIndex::canAccess()) {
            $items[] = NavigationItem::make(ConsoleIndex::getNavigationLabel())
                ->icon('heroicon-o-command-line')
                ->isActiveWhen(fn () => request()->routeIs(ConsoleIndex::getRouteName().'*'))
                ->url(ConsoleIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (LogsIndex::canAccess()) {
            $items[] = NavigationItem::make(LogsIndex::getNavigationLabel())
                ->icon('heroicon-o-square-3-stack-3d')
                ->isActiveWhen(fn () => request()->routeIs(LogsIndex::getRouteName().'*'))
                ->url(LogsIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (ServerSettings::canAccess()) {
            $items[] = NavigationItem::make(ServerSettings::getNavigationLabel())
                ->icon('heroicon-o-wrench-screwdriver')
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
