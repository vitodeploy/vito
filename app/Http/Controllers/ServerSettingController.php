<?php

namespace App\Http\Controllers;

use App\Actions\Server\EditServer;
use App\Actions\Server\RebootServer;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServerSettingController extends Controller
{
    public function index(Server $server): View
    {
        return view('server-settings.index', compact('server'));
    }

    public function checkConnection(Server $server): RedirectResponse|HtmxResponse
    {
        $oldStatus = $server->status;

        $server = $server->checkConnection();

        if ($server->status == 'disconnected') {
            Toast::error('Server is disconnected.');
        }

        if ($server->status == 'ready') {
            Toast::success('Server is ready.');
        }

        if ($oldStatus != $server->status) {
            return htmx()->redirect(back()->getTargetUrl());
        }

        return back();
    }

    public function reboot(Server $server): HtmxResponse
    {
        app(RebootServer::class)->reboot($server);

        Toast::info('Server is rebooting.');

        return htmx()->redirect(back()->getTargetUrl());
    }

    public function edit(Request $request, Server $server): RedirectResponse
    {
        app(EditServer::class)->edit($server, $request->input());

        Toast::success('Server updated.');

        return back();
    }
}
