<?php

namespace App\Http\Controllers;

use App\Facades\Toast;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ServiceController extends Controller
{
    public function index(Server $server): View
    {
        return view('services.index', [
            'server' => $server,
            'services' => $server->services,
        ]);
    }

    public function start(Server $server, Service $service): RedirectResponse
    {
        $service->start();

        Toast::success('Service is being started!');

        return back();
    }

    public function stop(Server $server, Service $service): RedirectResponse
    {
        $service->stop();

        Toast::success('Service is being stopped!');

        return back();
    }

    public function restart(Server $server, Service $service): RedirectResponse
    {
        $service->restart();

        Toast::success('Service is being restarted!');

        return back();
    }
}
