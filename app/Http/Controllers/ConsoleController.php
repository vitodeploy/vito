<?php

namespace App\Http\Controllers;

use App\Actions\Console\GenerateTerminalToken;
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
    /**
     * Render the terminal page (opened in a popup window).
     */
    #[Get('/', name: 'console')]
    public function index(Server $server): Response
    {
        $this->authorize('update', $server);

        return Inertia::render('servers/console', [
            'server' => ServerResource::make($server),
        ]);
    }

    /**
     * Generate a one-time token for WebSocket terminal authentication.
     */
    #[Post('/token', name: 'console.token')]
    public function token(Server $server, Request $request): JsonResponse
    {
        $this->authorize('update', $server);

        $this->validate($request, [
            'user' => [
                'required',
                Rule::in($server->getSshUsers()),
            ],
        ]);

        $result = app(GenerateTerminalToken::class)->generate(
            $server,
            $request->user(),
            $request->input('user'),
        );

        // Build the WebSocket URL
        $appUrl = parse_url(config('app.url'));
        $isSecure = ($appUrl['scheme'] ?? 'http') === 'https';
        $wsProtocol = $isSecure ? 'wss' : 'ws';
        $host = $appUrl['host'] ?? 'localhost';
        $port = $appUrl['port'] ?? ($isSecure ? 443 : 80);

        // In production, nginx proxies /ws/ to the WebSocket server
        // In development, connect directly to the WebSocket server port
        if (app()->environment('local')) {
            $wsPort = config('core.ws_port', 8085);
            $result['url'] = "{$wsProtocol}://{$host}:{$wsPort}/ws/terminal";
        } else {
            $portSuffix = (($isSecure && $port == 443) || (! $isSecure && $port == 80)) ? '' : ":{$port}";
            $result['url'] = "{$wsProtocol}://{$host}{$portSuffix}/ws/terminal";
        }

        return response()->json($result);
    }
}
