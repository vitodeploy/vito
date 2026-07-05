<?php

namespace App\Http\Middleware;

use App\Support\DesktopRuntime;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        return route(DesktopRuntime::enabled() ? 'desktop.login' : 'login');
    }
}
