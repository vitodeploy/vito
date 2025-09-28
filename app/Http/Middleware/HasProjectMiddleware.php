<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class HasProjectMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var ?User $user */
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! $user->currentProject) {
            if ($user->projects()->count() > 0) {
                $user->ensureHasDefaultProject();
                $user->refresh();

                return redirect()->route('servers');
            }

            abort(403, 'You must have a project to access the panel.');
        }

        return $next($request);
    }
}
