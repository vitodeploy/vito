<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CanSeeProjectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Project $project */
        $project = $request->route('project');

        if (! $user->can('view', $project)) {
            abort(403, 'You do not have permission to view this project.');
        }

        return $next($request);
    }
}
