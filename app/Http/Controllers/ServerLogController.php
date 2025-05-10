<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerLog;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/logs')]
#[Middleware(['auth', 'has-project'])]
class ServerLogController extends Controller
{
    #[Get('/{log}', name: 'logs.show')]
    public function show(Server $server, ServerLog $log): string
    {
        $this->authorize('view', $log);

        return $log->getContent();
    }
}
