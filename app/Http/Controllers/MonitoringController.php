<?php

namespace App\Http\Controllers;

use App\Actions\Monitoring\GetMetrics;
use App\Actions\Monitoring\UpdateMetricSettings;
use App\Enums\ServiceStatus;
use App\Models\Metric;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/monitoring')]
#[Middleware(['auth', 'has-project'])]
class MonitoringController extends Controller
{
    #[Get('/', name: 'monitoring')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [Metric::class, $server]);

        return Inertia::render('monitoring/index', [
            'dataRetention' => $server->monitoring()?->type_data['data_retention'] ?? 30,
            'hasMonitoringService' => $server->monitoring()?->status === ServiceStatus::READY,
        ]);
    }

    #[Get('/json', name: 'monitoring.json')]
    public function json(Request $request, Server $server): JsonResponse
    {
        $this->authorize('viewAny', [Metric::class, $server]);

        $metrics = app(GetMetrics::class)->filter($server, $request->input());

        return response()->json($metrics);
    }

    #[Get('/{metric}', name: 'monitoring.show')]
    public function show(Server $server, string $metric): Response
    {
        if (! in_array($metric, ['load', 'memory', 'disk'])) {
            abort(404);
        }

        $this->authorize('viewAny', [Metric::class, $server]);

        return Inertia::render('monitoring/show', [
            'metric' => $metric,
        ]);
    }

    #[Patch('/update', name: 'monitoring.update')]
    public function update(Request $request, Server $server): RedirectResponse
    {
        /** @var ?Service $monitoring */
        $monitoring = $server->monitoring();

        if (! $monitoring) {
            abort(404);
        }

        $this->authorize('update', $monitoring);

        app(UpdateMetricSettings::class)->update($server, $request->input());

        return back()->with('success', 'Settings updated!');
    }

    #[Delete('/reset', name: 'monitoring.destroy')]
    public function destroy(Server $server): RedirectResponse
    {
        /** @var ?Service $monitoring */
        $monitoring = $server->monitoring();

        if (! $monitoring) {
            abort(404);
        }

        $this->authorize('update', $monitoring);

        $server->metrics()->delete();

        return back()->with('success', 'All metrics deleted!');
    }
}
