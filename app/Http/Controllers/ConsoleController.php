<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsoleController extends Controller
{
    public function index(Server $server): View
    {
        $this->authorize('manage', $server);

        return view('console.index', [
            'server' => $server,
        ]);
    }

    public function run(Server $server, Request $request)
    {
        $this->authorize('manage', $server);

        $this->validate($request, [
            'user' => [
                'required',
                Rule::in(['root', $server->ssh_user]),
            ],
            'command' => 'required|string',
        ]);

        return response()->stream(
            function () use ($server, $request) {
                $ssh = $server->ssh($request->user);
                $log = 'console-'.time();
                $ssh->exec(command: $request->command, log: $log, stream: true);
            },
            200,
            [
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'Content-Type' => 'text/event-stream',
            ]
        );
    }
}
