<?php

namespace App\Providers;

use App\Helpers\Notifier;
use App\Helpers\SSH;
use App\Support\SocialiteProviders\DropboxProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Resources\Json\ResourceCollection;
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
        ResourceCollection::withoutWrapping();

        // facades
        $this->app->bind('ssh', function () {
            return new SSH;
        });
        $this->app->bind('notifier', function () {
            return new Notifier;
        });

        $this->extendSocialite();
    }

    /**
     * @throws BindingResolutionException
     */
    private function extendSocialite(): void
    {
        $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
        $socialite->extend(
            'dropbox',
            function ($app) use ($socialite) {
                $config = $app['config']['services.dropbox'];

                return $socialite->buildProvider(DropboxProvider::class, $config);
            }
        );
    }
}
