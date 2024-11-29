<?php

namespace App\Web\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\RecoveryCodeReplaced;

class TwoFactor extends SimplePage implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'components.simple-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Checkbox::make('use_recovery_code')
                    ->inline()
                    ->live()
                    ->label('Use recovery code')
                    ->default(false)
                    ->afterStateUpdated(function (Set $set): void {
                        $set('code', '');
                        $set('recovery_code', '');
                    }),
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->hidden(fn (Get $get) => $get('use_recovery_code'))
                    ->disabled(fn (Get $get) => $get('use_recovery_code')),
                TextInput::make('recovery_code')
                    ->label('Recovery code')
                    ->required(fn (Get $get) => $get('use_recovery_code'))
                    ->hidden(fn (Get $get) => ! $get('use_recovery_code'))
                    ->disabled(fn (Get $get) => ! $get('use_recovery_code')),
            ])->statePath('data');
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('filament.app.auth.login');
    }

    public function save()
    {
        $data = $this->form->getState();

        if ($data['use_recovery_code'] && $this->validRecoveryCode($data) == true) {
            $user = auth()->user();
            $code = $data['recovery_code'];
            $user->replaceRecoveryCode($code);
            event(new RecoveryCodeReplaced($user, $code));
            Session::put('two_factor_approved', true);
            Notification::make()
                ->success()
                ->title('Two-Factor Authentication has been approved!')
                ->send();

            return redirect()->route('filament.app.pages.servers');

        } elseif (! $data['use_recovery_code'] && $this->hasValidCode($data) == true) {
            Session::put('two_factor_approved', true);
            Notification::make()
                ->success()
                ->title('Two-Factor Authentication has been approved!')
                ->send();

            return redirect()->route('filament.app.pages.servers');

        } else {
            Notification::make()
                ->danger()
                ->title('The code is incorrect!')
                ->send();

            return;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Confirm')
                ->submit('save'),
        ];
    }

    protected function getLogoutActions(): array
    {
        return [
            Action::make('save')
                ->label('or you can logout')
                ->icon('heroicon-o-arrow-left-end-on-rectangle')
                ->submit('logout')
                ->color('danger')
                ->link(),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    private function validRecoveryCode($data)
    {
        if (! isset($data['recovery_code'])) {
            return false;
        }

        $recovery_code = $data['recovery_code'];
        $collection = auth()->user()->recoveryCodes();

        return collect($collection)
            ->first(function ($code) use ($recovery_code) {
                return hash_equals($code, $recovery_code) ? $code : false;
            }
            );
    }

    private function hasValidCode($data)
    {
        if (! isset($data['code'])) {
            return false;
        }

        $code = $data['code'];

        return $code && app(TwoFactorAuthenticationProvider::class)->verify(
            decrypt(auth()->user()->two_factor_secret), $code);
    }
}
