<?php

namespace App\Http\Controllers\API;

use App\Actions\Monitoring\StoreAgentMetric;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Post;

class AgentController extends Controller
{
    #[Post('api/servers/{server}/agent/{id}', name: 'api.servers.agent')]
    public function __invoke(Request $request, Server $server, int $id): JsonResponse
    {
        /** @var ?Service $service */
        $service = $server->services()->find($id);

        $expected = $service?->handler()->data()['secret'] ?? null;
        $provided = (string) $request->header('secret');

        if (! $service || ! is_string($expected) || $expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        app(StoreAgentMetric::class)->store($server, (array) $request->input());

        return response()->json();
    }
}
