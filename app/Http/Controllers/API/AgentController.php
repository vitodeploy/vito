<?php

namespace App\Http\Controllers\API;

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
        /** @var Service $service */
        $service = $server->services()->findOrFail($id);

        if ($request->header('secret') !== $service->handler()->data()['secret']) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $this->validate($request, [
            'load' => 'required|numeric',
            'memory_total' => 'required|numeric',
            'memory_used' => 'required|numeric',
            'memory_free' => 'required|numeric',
            'disk_total' => 'required|numeric',
            'disk_used' => 'required|numeric',
            'disk_free' => 'required|numeric',
            'cpu_cores' => 'nullable|integer',
            'cpu_physical_cores' => 'nullable|integer',
            'cpu_usage_percent' => 'nullable|numeric',
            'cpu_per_core_usage_percent' => 'nullable|array',
            'cpu_per_core_usage_percent.*' => 'numeric',
            'cpu_steal_percent' => 'nullable|numeric',
            'memory_used_percent' => 'nullable|numeric',
            'swap_total' => 'nullable|numeric',
            'swap_used' => 'nullable|numeric',
            'swap_free' => 'nullable|numeric',
            'swap_used_percent' => 'nullable|numeric',
            'oom_kill_count' => 'nullable|integer',
            'uptime_seconds' => 'nullable|numeric',
            'reboot_required' => 'nullable|boolean',
        ]);

        $server->metrics()->create(array_merge($validated, ['server_id' => $server->id]));

        return response()->json();
    }
}
