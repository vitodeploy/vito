<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MustBeAdminMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! user()->isAdmin()) {
            abort(403, 'You must be an admin to perform this action.');
        }

        return $next($request);
    }
}
