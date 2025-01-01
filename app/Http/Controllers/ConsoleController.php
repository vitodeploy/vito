<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;

#[Middleware('auth')]
class ConsoleController extends Controller
{
    #[Post('servers/{server}/console/run', name: 'servers.console.run')]
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

        $ssh = $server->ssh($request->user);
        $log = 'console-'.time();

        $user = $request->input('user');
        $currentDir = $user == 'root' ? '/root' : '/home/'.$user;
        if (Cache::has('console.'.$server->id.'.dir')) {
            $currentDir = Cache::get('console.'.$server->id.'.dir');
        }

        return response()->stream(
            function () use ($server, $request, $ssh, $log, $currentDir) {
                $command = 'cd '.$currentDir.' && '.$request->command.' && echo "VITO_WORKING_DIR: $(pwd)"';
                $output = '';
                $ssh->exec(command: $command, log: $log, stream: true, streamCallback: function ($out) use (&$output) {
                    echo preg_replace('/^VITO_WORKING_DIR:.*(\r?\n)?/m', '', $out);
                    $output .= $out;
                    ob_flush();
                    flush();
                });
                // extract the working dir and put it in the session
                if (preg_match('/VITO_WORKING_DIR: (.*)/', $output, $matches)) {
                    Cache::put('console.'.$server->id.'.dir', $matches[1]);
                }
            },
            200,
            [
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'Content-Type' => 'text/event-stream',
            ]
        );
    }

    #[Get('servers/{server}/console/working-dir', name: 'servers.console.working-dir')]
    public function workingDir(Server $server)
    {
        return response()->json([
            'dir' => Cache::get('console.'.$server->id.'.dir'),
        ]);
    }
}
