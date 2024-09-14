<?php

namespace App\Web\Resources\Profile\Widgets;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactor extends Widget implements HasForms
{
    use InteractsWithForms;

    protected $listeners = [
        'updated' => '$refresh',
    ];

    protected static string $view = 'web.profile.widgets.two-factor';

    public bool $enabled = false;

    public bool $showCodes = false;

    public function mount(): void
    {
        if (auth()->user()->two_factor_secret) {
            $this->enabled = true;
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Actions::make([
                Action::make('enable')
                    ->color('primary')
                    ->label('Enable')
                    ->visible(! $this->enabled)
                    ->action(fn () => $this->enableTwoFactor()),
                Action::make('disable')
                    ->color('danger')
                    ->label('Disable')
                    ->visible($this->enabled)
                    ->action(fn () => $this->disableTwoFactor()),
                Action::make('regenerate')
                    ->color('gray')
                    ->label('Regenerate Recovery Codes')
                    ->visible($this->enabled)
                    ->action(fn () => $this->regenerateRecoveryCodes()),
            ]),
        ]);
    }

    public function enableTwoFactor(): void
    {
        app(EnableTwoFactorAuthentication::class)(auth()->user());

        $this->enabled = true;
        $this->showCodes = true;

        Notification::make()
            ->success()
            ->title('Two factor authentication enabled')
            ->send();

        $this->dispatch('updated');
    }

    public function disableTwoFactor(): void
    {
        app(DisableTwoFactorAuthentication::class)(auth()->user());

        $this->enabled = false;
        $this->showCodes = false;

        Notification::make()
            ->success()
            ->title('Two factor authentication disabled')
            ->send();

        $this->dispatch('updated');
    }

    public function regenerateRecoveryCodes(): void
    {
        app(GenerateNewRecoveryCodes::class)(auth()->user());

        Notification::make()
            ->success()
            ->title('Recovery codes generated')
            ->send();

        $this->dispatch('updated');
    }
}
