<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('settings/profile')]
#[Middleware(['auth'])]
class ProfileController extends Controller
{
    #[Get('/', name: 'profile')]
    public function edit(Request $request): Response
    {
        return Inertia::render('profile/index', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    #[Patch('/', name: 'profile.update')]
    public function update(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore(user()->id),
            ],
        ]);
        $request->user()->fill($request->only('name', 'email'));

        $request->user()->save();

        return to_route('profile');
    }

    #[Put('/', name: 'profile.password')]
    public function password(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return to_route('profile');
    }

    #[Post('/enable-two-factor', name: 'profile.enable-two-factor')]
    public function enableTwoFactor(): RedirectResponse
    {
        $user = user();

        app(EnableTwoFactorAuthentication::class)($user);

        return back()
            ->with('success', 'Two factor authentication enabled.')
            ->with('data', [
                'qr_code' => $user->twoFactorQrCodeSvg(),
                'qr_code_url' => $user->twoFactorQrCodeUrl(),
                'recovery_codes' => $user->recoveryCodes(),
            ]);
    }

    #[Post('/disable-two-factor', name: 'profile.disable-two-factor')]
    public function disableTwoFactor(): RedirectResponse
    {
        $user = user();

        app(DisableTwoFactorAuthentication::class)($user);

        return back()->with('success', 'Two factor authentication disabled.');
    }
}
