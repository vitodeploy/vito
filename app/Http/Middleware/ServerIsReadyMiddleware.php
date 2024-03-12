<?php

namespace App\Http\Middleware;

use App\Enums\ServerStatus;
use App\Facades\Toast;
use App\Models\Server;
use Closure;
use Illuminate\Http\Request;

class ServerIsReadyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        /** @var Server $server */
        $server = $request->route('server');

        if ($server->status !== ServerStatus::READY) {
            Toast::error('Server is not ready yet');

            return redirect()->route('servers.show', ['server' => $server]);
        }

        return $next($request);
    }
}
