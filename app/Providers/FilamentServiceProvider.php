<?php

namespace App\Providers;

use App\Filament\Widgets\SelectProject;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Notifications::alignment(Alignment::Center);
        Notifications::verticalAlignment(VerticalAlignment::End);

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn () => Livewire::mount(SelectProject::class)
        );
    }
}
