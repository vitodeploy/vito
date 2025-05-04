<?php

namespace App\Providers;

use App\Helpers\FTP;
use App\Helpers\Notifier;
use App\Helpers\SSH;
use App\Models\PersonalAccessToken;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    public function boot(): void
    {
        ResourceCollection::withoutWrapping();

        // facades
        $this->app->bind('ssh', fn (): SSH => new SSH);
        $this->app->bind('notifier', fn (): Notifier => new Notifier);
        $this->app->bind('ftp', fn (): FTP => new FTP);

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
