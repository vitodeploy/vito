<?php

namespace App\Web\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;

class Login extends \Filament\Pages\Auth\Login
{
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->initTwoFactor();

        $this->form->fill();

        if (config('app.demo')) {
            $this->form->fill([
                'email' => 'demo@vitodeploy.com',
                'password' => 'password',
            ]);
        }
    }

    public function logoutAction(): Action
    {
        return Action::make('logout')
            ->label('Logout')
            ->color('danger')
            ->link()
            ->action(function () {
                Filament::auth()->logout();

                session()->forget('login.id');

                $this->redirect(Filament::getLoginUrl());
            });
    }

    protected function getForms(): array
    {
        if (request()->session()->has('login.id')) {
            return [
                'form' => $this->form(
                    $this->makeForm()
                        ->schema([
                            TextInput::make('code')
                                ->label('2FA Code')
                                ->autofocus(),
                            TextInput::make('recovery_code')
                                ->label('Recovery Code')
                                ->autofocus(),
                        ])
                        ->statePath('data'),
                ),
            ];
        }

        return parent::getForms();
    }

    public function authenticate(): ?LoginResponse
    {
        if (request()->session()->has('login.id')) {
            return $this->confirmTwoFactor();
        }

        $loginResponse = parent::authenticate();

        /** @var ?User $user */
        $user = Filament::auth()->getUser();
        if ($user && $user->two_factor_secret) {
            Filament::auth()->logout();

            request()->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $this->data['remember'] ?? false,
            ]);

            $this->redirect(Filament::getLoginUrl());

            return null;
        }

        return $loginResponse;
    }

    private function confirmTwoFactor(): ?LoginResponse
    {
        $request = TwoFactorLoginRequest::createFrom(request())->merge([
            'code' => $this->data['code'],
            'recovery_code' => $this->data['recovery_code'],
        ]);

        /** @var ?User $user */
        $user = $request->challengedUser();

        if ($code = $request->validRecoveryCode()) {
            $user->replaceRecoveryCode($code);
        } elseif (! $request->hasValidCode()) {
            $field = $request->input('recovery_code') ? 'recovery_code' : 'code';

            $this->initTwoFactor();

            throw ValidationException::withMessages([
                'data.'.$field => 'Invalid code!',
            ]);
        }

        Filament::auth()->login($user, $request->remember());

        return app(LoginResponse::class);
    }

    protected function getAuthenticateFormAction(): Action
    {
        if (request()->session()->has('login.id')) {
            return Action::make('verify')
                ->label('Verify')
                ->submit('authenticate');
        }

        return parent::getAuthenticateFormAction();
    }

    private function initTwoFactor(): void
    {
        if (request()->session()->has('login.id')) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => Blade::render(
                    <<<BLADE
                        <x-slot name="subheading">{$this->logoutAction()->render()}</x-slot>
                    BLADE
                ),
            );
        }
    }
}
