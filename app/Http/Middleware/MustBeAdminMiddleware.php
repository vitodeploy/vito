<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MustBeAdminMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->user()?->isAdmin()) {
            abort(404);
        }

        return $next($request);
    }
}
