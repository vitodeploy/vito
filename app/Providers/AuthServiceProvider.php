<?php

namespace App\Providers;

use App\Actions\User\ResetUserPassword;
use App\Actions\User\UpdateUserPassword;
use App\Actions\User\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::loginView(fn () => Inertia::render('auth/login'));
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('auth/forgot-password'));
        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));
        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor'));

        AuthenticateSession::redirectUsing(fn (): string => route('login'));

        ResetPassword::createUrlUsing(fn (User $user, string $token): string => app_url(route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false)));

        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(Str::lower($request->string(Fortify::username())->toString()));

            return array_filter([
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                $email !== '' ? Limit::perMinute(10)->by('email|'.$email) : null,
            ]);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
