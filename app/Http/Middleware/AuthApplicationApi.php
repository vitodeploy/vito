<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Closure;

class AuthApplicationApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // if (!$request->bearerToken()) {
        //     return response()->json(['success' => false, 'errors' => ['Bearer token not provided']], 401);
        // }

        // if ($request->bearerToken() !== 'vito999') {
        //     return response()->json(['success' => false, 'errors' => ['Bearer token is not valid']], 403);
        // }

        return $next($request);
    }    
}