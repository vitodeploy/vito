<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ServerLogController extends Controller
{
    public function index(Server $server): View
    {
        return view('server-logs.index', [
            'server' => $server,
        ]);
    }

    public function show(Server $server, ServerLog $serverLog): RedirectResponse
    {
        if ($server->id != $serverLog->server_id) {
            abort(404);
        }

        return back()->with([
            'content' => $serverLog->getContent(),
        ]);
    }
}
