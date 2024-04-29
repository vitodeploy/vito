<?php

namespace App\Http\Controllers;

use App\Actions\Monitoring\GetMetrics;
use App\Actions\Monitoring\UpdateMetricSettings;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MetricController extends Controller
{
    public function index(Server $server, Request $request): View|RedirectResponse
    {
        $this->authorize('manage', $server);

        $this->checkIfMonitoringServiceInstalled($server);

        return view('metrics.index', [
            'server' => $server,
            'data' => app(GetMetrics::class)->filter($server, $request->input()),
            'lastMetric' => $server->metrics()->latest()->first(),
        ]);
    }

    public function settings(Server $server, Request $request): HtmxResponse
    {
        $this->authorize('manage', $server);

        $this->checkIfMonitoringServiceInstalled($server);

        app(UpdateMetricSettings::class)->update($server, $request->input());

        Toast::success('Metric settings updated successfully');

        return htmx()->back();
    }

    private function checkIfMonitoringServiceInstalled(Server $server): void
    {
        $this->authorize('manage', $server);

        if (! $server->monitoring()) {
            abort(404, 'Monitoring service is not installed on this server');
        }
    }
}
