<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/profile')]
#[Middleware(['auth'])]
class ProfileController extends Controller
{
    #[Get('/', name: 'profile')]
    public function edit(Request $request): Response
    {
        $user = user();

        return Inertia::render('profile/index', [
            'two_factor_enabled' => (bool) $user->two_factor_secret,
            'two_factor_confirmed_at' => $user->two_factor_confirmed_at,
            'two_factor_must_confirm' => (bool) config('fortify-options.two-factor-authentication.confirm'),
            'status' => $request->session()->get('status'),
        ]);
    }
}
