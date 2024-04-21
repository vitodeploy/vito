<?php

namespace App\Http\Middleware;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Facades\Toast;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HandleSSHErrors
{
    public function handle(Request $request, Closure $next)
    {
        $res = $next($request);
        // if ($res instanceof Response && $res->exception) {
        //     if ($res->exception instanceof SSHConnectionError || $res->exception instanceof SSHCommandError) {
        //         Toast::error($res->exception->getMessage());

        //         if ($request->hasHeader('HX-Request')) {
        //             return htmx()->back();
        //         }

        //         return back();
        //     }
        // }

        return $res;
    }
}
