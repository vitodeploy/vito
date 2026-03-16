<?php

namespace App\Providers;

use App\DNSProviders\Cloudflare;
use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Plugins\RegisterDNSProvider;
use Illuminate\Support\ServiceProvider;

class DNSProviderServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->cloudflare();
    }

    private function cloudflare(): void
    {
        RegisterDNSProvider::make(Cloudflare::id())
            ->label('Cloudflare')
            ->handler(Cloudflare::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('API Token')
                        ->description('Create an API token with Zone:Read and DNS:Edit permissions'),
                ])
            )
            ->editForm(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->passwordWithToggle()
                        ->label('API Token')
                        ->description('Leave empty to keep the current token'),
                ])
            )
            ->proxyTypes(['A', 'AAAA', 'CNAME'])
            ->supportsCreatedAt(true)
            ->register();
    }
}
