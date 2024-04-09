<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __invoke(Request $request, Server $server, int $id): JsonResponse
    {
        $validated = $this->validate($request, [
            'load' => 'required|numeric',
            'memory_total' => 'required|numeric',
            'memory_used' => 'required|numeric',
            'memory_free' => 'required|numeric',
            'disk_total' => 'required|numeric',
            'disk_used' => 'required|numeric',
            'disk_free' => 'required|numeric',
        ]);

        /** @var Service $service */
        $service = $server->services()->findOrFail($id);

        if ($request->header('secret') !== $service->handler()->data()['secret']) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $server->metrics()->create(array_merge($validated, ['server_id' => $server->id]));

        return response()->json();
    }
}
