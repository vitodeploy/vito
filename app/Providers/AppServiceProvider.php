<?php

namespace App\Providers;

use App\Helpers\FTP;
use App\Helpers\Notifier;
use App\Helpers\SSH;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

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
        $this->app->bind('ssh', function () {
            return new SSH;
        });
        $this->app->bind('notifier', function () {
            return new Notifier;
        });
        $this->app->bind('ftp', function () {
            return new FTP;
        });
    }
}
