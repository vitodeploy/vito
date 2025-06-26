<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedTwoFactorLoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthenticatedSessionController extends Controller
{
    protected StatefulGuard $guard;

    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }

    #[Get('two-factor', name: 'two-factor.login')]
    public function create(TwoFactorLoginRequest $request): \Inertia\Response
    {
        if (! $request->hasChallengedUser()) {
            throw new HttpResponseException(redirect()->route('login'));
        }

        return Inertia::render('auth/two-factor');
    }

    #[Post('two-factor', name: 'two-factor.store')]
    public function store(TwoFactorLoginRequest $request): TwoFactorLoginResponse|Response
    {
        /** @var User $user */
        $user = $request->challengedUser();

        if ($code = $request->validRecoveryCode()) {
            $user->replaceRecoveryCode($code);

            event(new RecoveryCodeReplaced($user, $code));
        } elseif (! $request->hasValidCode()) {
            event(new TwoFactorAuthenticationFailed($user));

            return app(FailedTwoFactorLoginResponse::class)->toResponse($request);
        }

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        $this->guard->login($user, $request->remember());

        $request->session()->regenerate();

        return redirect()->intended(route('servers', absolute: false));
    }
}
