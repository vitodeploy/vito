<?php

namespace App\Http\Middleware;

use App\Support\DesktopRuntime;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDesktopMode
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(DesktopRuntime::enabled(), 404);

        return $next($request);
    }
}
