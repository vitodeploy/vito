<?php

namespace App\Providers;

use App\Web;
use App\Web\Resources\Project\Widgets\SelectProject;
use Exception;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Livewire;

class WebServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Filament::registerPanel(
            fn (): Panel => $this->panel(Panel::make()),
        );
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn () => Livewire::mount(SelectProject::class)
        );
    }

    /**
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->passwordReset()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->brandLogo(fn () => view('web.components.brand'))
            ->brandLogoHeight('30px')
            ->pages([
                Web\Pages\Dashboard::class,
            ])
            ->resources([
                Web\Resources\Server\ServerResource::class,
                Web\Resources\Profile\ProfileResource::class,
                Web\Resources\Project\ProjectResource::class,
                Web\Resources\User\UserResource::class,
                Web\Resources\ServerProvider\ServerProviderResource::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
