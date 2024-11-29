<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();
        if ($user->hasEnabledTwoFactorAuthentication()) {
            if (Session::has('two_factor_approved') && ! Session::get('two_factor_approved')) {
                if ($request->route()->getName() != 'filament.app.two-factor' && $request->route()->getName() != 'filament.app.auth.logout') {
                    return redirect()->route('filament.app.two-factor');
                }
            }
        }

        if (! $user->hasEnabledTwoFactorAuthentication() && Session::has('two_factor_approved')) {
            Session::forget('two_factor_approved');
        }

        if ($user->hasEnabledTwoFactorAuthentication() && Session::get('two_factor_approved') && $request->route()->getName() === 'filament.app.two-factor') {
            return redirect()->route('filament.app.pages.servers');
        }

        if (($request->route()->getName() === 'filament.app.two-factor') && ! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('filament.app.pages.servers');
        }

        return $next($request);
    }
}
