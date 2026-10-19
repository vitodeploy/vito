<?php

namespace App\Providers;

use App\Events\SiteCreatedEvent;
use App\Events\SiteDeletedEvent;
use App\Events\SocketEvent;
use App\Helpers\FTP;
use App\Helpers\Notifier;
use App\Helpers\SFTP;
use App\Helpers\SSH;
use App\Listeners\HandleSiteCreatedStats;
use App\Listeners\HandleSiteDeletedStats;
use App\Listeners\SocketEventListener;
use App\Models\PersonalAccessToken;
use App\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    public function boot(): void
    {
        ResourceCollection::withoutWrapping();

        TrimStrings::skipWhen(fn ($request): bool => $request->is('applications/*/environments/*/variables*'));
        ConvertEmptyStringsToNull::skipWhen(fn ($request): bool => $request->is('applications/*/environments/*/variables*'));

        // facades
        $this->app->bind('ssh', fn (): SSH => new SSH);
        $this->app->bind('notifier', fn (): Notifier => new Notifier);
        $this->app->bind('ftp', fn (): FTP => new FTP);
        $this->app->bind('sftp', fn (): SFTP => new SFTP);

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        if (config('app.force_https')) {
            URL::forceHttps();
        }

        Event::listen('eloquent.deleting: '.\App\Models\Project::class, fn (\App\Models\Project $project) => app(\App\Actions\GitHook\SyncRepositoryGitHook::class)->disableProject($project));
        Event::listen('eloquent.saved: '.\App\Models\NetworkServer::class, fn (\App\Models\NetworkServer $member) => app(\App\Actions\Component\SyncLoadBalancerBackends::class)->membershipChanged($member));
        Event::listen('eloquent.saved: '.\App\Models\NetworkServer::class, fn (\App\Models\NetworkServer $member) => app(\App\Actions\EnvironmentAttachment\SyncEnvironmentResourceAccess::class)->membershipChanged($member));
        Event::listen(SocketEvent::class, SocketEventListener::class);
        Event::listen(SiteCreatedEvent::class, HandleSiteCreatedStats::class);
        Event::listen(SiteDeletedEvent::class, HandleSiteDeletedStats::class);
    }
}
