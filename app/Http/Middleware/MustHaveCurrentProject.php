<?php

namespace App\Http\Middleware;

use App\Facades\Toast;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustHaveCurrentProject
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->currentProject) {
            Toast::warning('Please select a project to continue');

            return redirect()->route('profile');
        }

        return $next($request);
    }
}
