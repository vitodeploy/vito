<?php

namespace App\Web\Pages\Settings\Profile\Widgets;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactor extends Widget implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected $listeners = ['$refresh'];

    protected static bool $isLazy = false;

    protected static string $view = 'components.infolist';

    public bool $enabled = false;

    public bool $showCodes = false;

    public function mount(): void
    {
        if (auth()->user()->two_factor_secret) {
            $this->enabled = true;
        }
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()
                ->heading('Two Factor Authentication')
                ->description('Here you can activate 2FA to secure your account')
                ->schema([
                    TextEntry::make('disabled')
                        ->hiddenLabel()
                        ->state('Two factor authentication is disabled.')
                        ->visible(! $this->enabled),
                    ViewEntry::make('qr_code')
                        ->hiddenLabel()
                        ->view('components.container', [
                            'content' => $this->enabled ? auth()->user()->twoFactorQrCodeSvg() : null,
                        ])
                        ->visible($this->enabled && $this->showCodes),
                    TextEntry::make('qr_code_manual')
                        ->label('If you are unable to scan the QR code, please use the 2FA secret instead.')
                        ->state($this->enabled ? decrypt(auth()->user()->two_factor_secret) : null)
                        ->copyable()
                        ->visible($this->enabled && $this->showCodes),
                    TextEntry::make('recovery_codes_text')
                        ->hiddenLabel()
                        ->color('warning')
                        ->state('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.')
                        ->visible($this->enabled),
                    ViewEntry::make('recovery_codes')
                        ->hiddenLabel()
                        ->extraAttributes(['class' => 'rounded-lg border border-gray-100 p-2 dark:border-gray-700'])
                        ->view('components.container', [
                            'content' => $this->enabled ? implode('</br>', json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true)) : null,
                        ])
                        ->visible($this->enabled),
                ])
                ->footerActions([
                    Action::make('two-factor')
                        ->color($this->enabled ? 'danger' : 'primary')
                        ->label($this->enabled ? 'Disable' : 'Enable')
                        ->action(function () {
                            if ($this->enabled) {
                                $this->disableTwoFactor();
                            } else {
                                $this->enableTwoFactor();
                            }
                        }),
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

        $this->dispatch('$refresh');
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

        $this->dispatch('$refresh');
    }

    public function regenerateRecoveryCodes(): void
    {
        app(GenerateNewRecoveryCodes::class)(auth()->user());

        Notification::make()
            ->success()
            ->title('Recovery codes generated')
            ->send();

        $this->dispatch('$refresh');
    }
}
