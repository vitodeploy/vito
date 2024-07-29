<?php

namespace App\Http\Controllers;

use App\Actions\Service\Install;
use App\Actions\Service\Uninstall;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Server $server): View
    {
        $this->authorize('manage', $server);

        return view('services.index', [
            'server' => $server,
            'services' => $server->services,
        ]);
    }

    public function start(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('manage', $server);

        $service->start();

        Toast::success('Service is being started!');

        return back();
    }

    public function stop(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('manage', $server);

        $service->stop();

        Toast::success('Service is being stopped!');

        return back();
    }

    public function restart(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('manage', $server);

        $service->restart();

        Toast::success('Service is being restarted!');

        return back();
    }

    public function enable(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('manage', $server);

        $service->enable();

        Toast::success('Service is being enabled!');

        return back();
    }

    public function disable(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('manage', $server);

        $service->disable();

        Toast::success('Service is being disabled!');

        return back();
    }

    public function install(Server $server, Request $request): HtmxResponse
    {
        $this->authorize('manage', $server);

        app(Install::class)->install($server, $request->input());

        Toast::success('Service is being installed!');

        return htmx()->back();
    }

    public function uninstall(Server $server, Service $service): HtmxResponse
    {
        $this->authorize('manage', $server);

        app(Uninstall::class)->uninstall($service);

        Toast::success('Service is being uninstalled!');

        return htmx()->back();
    }
}
