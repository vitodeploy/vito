<?php

namespace App\Providers;

use App\Web\Pages\Settings\Projects\Widgets\SelectProject;
use Exception;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
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
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn () => view('components.app-version')
        );
        FilamentAsset::register([
            Js::make('app', Vite::asset('resources/js/app.js'))->module(),
        ]);
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
            ->path('')
            ->passwordReset()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->brandLogo(fn () => view('components.brand'))
            ->brandLogoHeight('30px')
            ->discoverPages(in: app_path('Web/Pages'), for: 'App\\Web\\Pages')
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
            ->login()
            ->spa()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchFieldKeyBindingSuffix();
    }
}
