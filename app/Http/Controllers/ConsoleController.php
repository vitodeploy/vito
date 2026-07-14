<?php

namespace App\Http\Controllers;

use App\Actions\WebSockets\GenerateWebSocketToken;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/console')]
#[Middleware(['auth', 'has-project'])]
class ConsoleController extends Controller
{
    #[Get('/', name: 'console')]
    public function index(Server $server): Response
    {
        $this->authorize('update', $server);

        return Inertia::render('servers/console', [
            'server' => ServerResource::make($server),
            'ssh_users' => $server->sshLoginUsers(),
        ]);
    }

    #[Post('/token', name: 'console.token')]
    public function token(Server $server, Request $request): JsonResponse
    {
        $this->authorize('update', $server);

        $this->validate($request, [
            'user' => [
                'required',
                Rule::in($server->sshLoginUsers()),
            ],
        ]);

        $result = app(GenerateWebSocketToken::class)->generate('terminal_token', [
            'server_id' => $server->id,
            'user_id' => $request->user()->id,
            'ssh_user' => $request->input('user'),
        ]);

        $appUrl = parse_url(config('app.ws_url') ?: config('app.url'));
        $isSecure = ($appUrl['scheme'] ?? 'http') === 'https';
        $wsProtocol = $isSecure ? 'wss' : 'ws';
        $host = $appUrl['host'] ?? 'localhost';
        $port = $appUrl['port'] ?? ($isSecure ? 443 : 80);

        if (app()->environment('local') && ! config('app.ws_url')) {
            $wsPort = config('core.ws_port', 8085);
            $result['url'] = "{$wsProtocol}://{$host}:{$wsPort}/ws/terminal";
        } else {
            $portSuffix = (($isSecure && $port == 443) || (! $isSecure && $port == 80)) ? '' : ":{$port}";
            $result['url'] = "{$wsProtocol}://{$host}{$portSuffix}/ws/terminal";
        }

        return response()->json($result);
    }
}
