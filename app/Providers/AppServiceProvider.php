<?php

namespace App\Providers;

use App\Helpers\Notifier;
use App\Helpers\SSH;
use App\Helpers\Toast;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        if (config('app.scheme') == 'https') {
            URL::forceScheme('https');
            request()->server->set('HTTPS', request()->header('X-Forwarded-Proto', 'https') == 'https' ? 'on' : 'off');
        }

        ResourceCollection::withoutWrapping();

        // facades
        $this->app->bind('ssh', function () {
            return new SSH;
        });
        $this->app->bind('notifier', function () {
            return new Notifier;
        });
        $this->app->bind('toast', function () {
            return new Toast;
        });
    }
}
