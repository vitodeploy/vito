<?php

namespace App\Providers;

use App\Web;
use App\Web\Resources\Project\Widgets\SelectProject;
use App\Web\Resources\Server\Widgets\SelectServer;
use Exception;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
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
    /**
     * @throws Exception
     */
    public function register(): void
    {
        Filament::registerPanel($this->panel(Panel::make()));
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn () => Livewire::mount(SelectProject::class)
        );
        //        FilamentView::registerRenderHook(
        //            PanelsRenderHook::SIDEBAR_NAV_START,
        //            fn () => Livewire::mount(SelectServer::class)
        //        );
        FilamentColor::register([
            'slate' => Color::Slate,
            'gray' => Color::Zinc,
            'red' => Color::Red,
            'orange' => Color::Orange,
            'amber' => Color::Amber,
            'yellow' => Color::Yellow,
            'lime' => Color::Lime,
            'green' => Color::Green,
            'emerald' => Color::Emerald,
            'teal' => Color::Teal,
            'cyan' => Color::Cyan,
            'sky' => Color::Sky,
            'blue' => Color::Blue,
            'indigo' => Color::Indigo,
            'violet' => Color::Violet,
            'purple' => Color::Purple,
            'fuchsia' => Color::Fuchsia,
            'pink' => Color::Pink,
            'rose' => Color::Rose,
        ]);
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
                Web\Resources\Profile\ProfileResource::class,
                Web\Resources\Project\ProjectResource::class,
                Web\Resources\User\UserResource::class,
                Web\Resources\ServerProvider\ServerProviderResource::class,

                Web\Resources\Server\ServerResource::class,
            ])
            ->discoverClusters(in: app_path('Web/Clusters'), for: 'App\\Web\\Clusters')
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
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix();
    }
}
