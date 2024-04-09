<?php

namespace App\Http\Controllers;

use App\Actions\Monitoring\GetMetrics;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MetricController extends Controller
{
    public function index(Server $server, Request $request): View
    {
        return view('metrics.index', [
            'server' => $server,
            'data' => app(GetMetrics::class)->filter($server, $request->input()),
        ]);
    }
}
