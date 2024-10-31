<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;

#[Middleware('auth')]
class ConsoleController extends Controller
{
    #[Post('/{server}/console', name: 'servers.console.run')]
    public function run(Server $server, Request $request)
    {
        $this->authorize('update', $server);

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
                $ssh->exec(command: $request->command, log: $log, stream: true, streamCallback: function ($output) {
                    echo $output;
                    ob_flush();
                    flush();
                });
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
