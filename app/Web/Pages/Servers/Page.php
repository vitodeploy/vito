<?php

namespace App\Web\Pages\Servers;

use App\Models\CronJob;
use App\Models\Database;
use App\Models\FirewallRule;
use App\Models\Metric;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Site;
use App\Models\SshKey;
use App\Web\Components\Page as BasePage;
use App\Web\Pages\Servers\Console\Index as ConsoleIndex;
use App\Web\Pages\Servers\CronJobs\Index as CronJobsIndex;
use App\Web\Pages\Servers\Databases\Index as DatabasesIndex;
use App\Web\Pages\Servers\Firewall\Index as FirewallIndex;
use App\Web\Pages\Servers\Logs\Index as LogsIndex;
use App\Web\Pages\Servers\Metrics\Index as MetricsIndex;
use App\Web\Pages\Servers\NodeJS\Index as NodeJsIndex;
use App\Web\Pages\Servers\PHP\Index as PHPIndex;
use App\Web\Pages\Servers\Services\Index as ServicesIndex;
use App\Web\Pages\Servers\Settings as ServerSettings;
use App\Web\Pages\Servers\Sites\Index as SitesIndex;
use App\Web\Pages\Servers\SSHKeys\Index as SshKeysIndex;
use App\Web\Pages\Servers\View as ServerView;
use App\Web\Pages\Servers\Widgets\ServerSummary;
use Filament\Navigation\NavigationItem;

abstract class Page extends BasePage
{
    public Server $server;

    protected static bool $shouldRegisterNavigation = false;

    public function getSubNavigation(): array
    {
        $items = [];

        if (auth()->user()->can('view', $this->server)) {
            $items[] = NavigationItem::make(ServerView::getNavigationLabel())
                ->icon('heroicon-o-chart-pie')
                ->isActiveWhen(fn () => request()->routeIs(ServerView::getRouteName()))
                ->url(ServerView::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAny', [Site::class, $this->server])) {
            $items[] = NavigationItem::make(SitesIndex::getNavigationLabel())
                ->icon('heroicon-o-cursor-arrow-ripple')
                ->isActiveWhen(fn () => request()->routeIs(SitesIndex::getRouteName().'*'))
                ->url(SitesIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAny', [Database::class, $this->server])) {
            $items[] = NavigationItem::make(DatabasesIndex::getNavigationLabel())
                ->icon('heroicon-o-circle-stack')
                ->isActiveWhen(fn () => request()->routeIs(DatabasesIndex::getRouteName().'*'))
                ->url(DatabasesIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAny', [Service::class, $this->server])) {
            $items[] = NavigationItem::make(PHPIndex::getNavigationLabel())
                ->icon('icon-php-alt')
                ->isActiveWhen(fn () => request()->routeIs(PHPIndex::getRouteName().'*'))
                ->url(PHPIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAny', [Service::class, $this->server])) {
            $items[] = NavigationItem::make(NodeJsIndex::getNavigationLabel())
                ->icon('icon-nodejs-alt')
                ->isActiveWhen(fn () => request()->routeIs(NodeJsIndex::getRouteName().'*'))
                ->url(NodeJsIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAny', [FirewallRule::class, $this->server])) {
            $items[] = NavigationItem::make(FirewallIndex::getNavigationLabel())
                ->icon('heroicon-o-fire')
                ->isActiveWhen(fn () => request()->routeIs(FirewallIndex::getRouteName().'*'))
                ->url(FirewallIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAny', [CronJob::class, $this->server])) {
            $items[] = NavigationItem::make(CronJobsIndex::getNavigationLabel())
                ->icon('heroicon-o-clock')
                ->isActiveWhen(fn () => request()->routeIs(CronJobsIndex::getRouteName().'*'))
                ->url(CronJobsIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAnyServer', [SshKey::class, $this->server])) {
            $items[] = NavigationItem::make(SshKeysIndex::getNavigationLabel())
                ->icon('heroicon-o-key')
                ->isActiveWhen(fn () => request()->routeIs(SshKeysIndex::getRouteName().'*'))
                ->url(SshKeysIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAny', [Service::class, $this->server])) {
            $items[] = NavigationItem::make(ServicesIndex::getNavigationLabel())
                ->icon('heroicon-o-cog-6-tooth')
                ->isActiveWhen(fn () => request()->routeIs(ServicesIndex::getRouteName().'*'))
                ->url(ServicesIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAny', [Metric::class, $this->server])) {
            $items[] = NavigationItem::make(MetricsIndex::getNavigationLabel())
                ->icon('heroicon-o-chart-bar')
                ->isActiveWhen(fn () => request()->routeIs(MetricsIndex::getRouteName().'*'))
                ->url(MetricsIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('manage', $this->server)) {
            $items[] = NavigationItem::make(ConsoleIndex::getNavigationLabel())
                ->icon('heroicon-o-command-line')
                ->isActiveWhen(fn () => request()->routeIs(ConsoleIndex::getRouteName().'*'))
                ->url(ConsoleIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('viewAny', [ServerLog::class, $this->server])) {
            $items[] = NavigationItem::make(LogsIndex::getNavigationLabel())
                ->icon('heroicon-o-square-3-stack-3d')
                ->isActiveWhen(fn () => request()->routeIs(LogsIndex::getRouteName().'*'))
                ->url(LogsIndex::getUrl(parameters: ['server' => $this->server]));
        }

        if (auth()->user()->can('update', $this->server)) {
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

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 1;
    }
}
