<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class HasProjectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        /** @var ?User $user */
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! $user->currentProject) {
            if ($user->allProjects()->count() > 0) {
                $user->current_project_id = $user->allProjects()->first()->id;
                $user->save();

                $request->user()->refresh();

                return $next($request);
            }

            abort(403, 'You must have a project to access the panel.');
        }

        return $next($request);
    }
}
