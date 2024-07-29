<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SelectCurrentProject
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Server $server */
        $server = $request->route('server');

        /** @var User $user */
        $user = $request->user();

        if ($server->project_id != $user->current_project_id && $user->can('view', $server)) {
            $user->current_project_id = $server->project_id;
            $user->save();
        }

        return $next($request);
    }
}
