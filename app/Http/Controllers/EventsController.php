<?php

namespace App\Http\Controllers;

use App\Actions\WebSockets\GenerateWebSocketToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;

#[Middleware(['auth', 'has-project'])]
class EventsController extends Controller
{
    #[Post('/events/token', name: 'events.token')]
    public function token(Request $request): JsonResponse
    {
        $result = app(GenerateWebSocketToken::class)->generate('events_token', [
            'user_id' => $request->user()->id,
            'project_id' => $request->user()->current_project_id,
        ]);

        $appUrl = parse_url(config('app.ws_url', config('app.url')));
        $isSecure = ($appUrl['scheme'] ?? 'http') === 'https';
        $wsProtocol = $isSecure ? 'wss' : 'ws';
        $host = $appUrl['host'] ?? 'localhost';
        $port = $appUrl['port'] ?? ($isSecure ? 443 : 80);

        if (app()->environment('local')) {
            $wsPort = config('core.ws_port', 8085);
            $result['url'] = "{$wsProtocol}://{$host}:{$wsPort}/ws/events";
        } else {
            $portSuffix = (($isSecure && $port == 443) || (! $isSecure && $port == 80)) ? '' : ":{$port}";
            $result['url'] = "{$wsProtocol}://{$host}{$portSuffix}/ws/events";
        }

        return response()->json($result);
    }
}
