<?php

namespace App\Providers;

use App\Actions\ApplicationType\DeployReverseProxy;
use App\ApplicationTypes\ReverseProxy;
use App\Plugins\RegisterApplicationType;
use Illuminate\Support\ServiceProvider;

class ApplicationTypeServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->reverseProxy();
    }

    private function reverseProxy(): void
    {
        RegisterApplicationType::make(ReverseProxy::id())
            ->label('Reverse Proxy')
            ->handler(ReverseProxy::class)
            ->deployAction(DeployReverseProxy::class)
            ->form(ReverseProxy::form())
            ->editForm(ReverseProxy::form())
            ->settingsFields(ReverseProxy::settingsFields())
            ->register();
    }
}
