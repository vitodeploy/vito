<?php

namespace App\Http\Controllers;

use App\Actions\Monitoring\GetMetrics;
use App\Facades\Toast;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MetricController extends Controller
{
    public function index(Server $server, Request $request): View|RedirectResponse
    {
        if (! $server->service('monitoring')) {
            Toast::error('You need to install monitoring service first');

            return redirect()->route('servers.services', $server);
        }

        return view('metrics.index', [
            'server' => $server,
            'data' => app(GetMetrics::class)->filter($server, $request->input()),
        ]);
    }
}
