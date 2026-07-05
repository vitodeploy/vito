<?php

namespace App\Http\Controllers;

use App\Actions\WebSockets\GenerateWebSocketToken;
use App\Support\DesktopRuntime;
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

        $result['url'] = DesktopRuntime::websocketUrl('/ws/events');

        return response()->json($result);
    }
}
