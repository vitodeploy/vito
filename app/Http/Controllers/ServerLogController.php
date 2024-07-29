<?php

namespace App\Http\Controllers;

use App\Actions\Server\CreateServerLog;
use App\Facades\Toast;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServerLogController extends Controller
{
    public function index(Server $server): View
    {
        $this->authorize('manage', $server);

        return view('server-logs.index', [
            'server' => $server,
            'pageTitle' => __('Vito Logs'),
        ]);
    }

    public function show(Server $server, ServerLog $serverLog): RedirectResponse
    {
        $this->authorize('manage', $server);

        if ($server->id != $serverLog->server_id) {
            abort(404);
        }

        return back()->with([
            'content' => $serverLog->getContent(),
        ]);
    }

    public function remote(Server $server): View
    {
        $this->authorize('manage', $server);

        return view('server-logs.remote-logs', [
            'server' => $server,
            'remote' => true,
            'pageTitle' => __('Remote Logs'),
        ]);
    }

    public function store(Server $server, Request $request): \App\Helpers\HtmxResponse
    {
        $this->authorize('manage', $server);

        app(CreateServerLog::class)->create($server, $request->input());

        Toast::success('Log added successfully.');

        return htmx()->redirect(route('servers.logs.remote', ['server' => $server]));
    }

    public function destroy(Server $server, ServerLog $serverLog): RedirectResponse
    {
        $this->authorize('manage', $server);

        $serverLog->delete();

        Toast::success('Remote log deleted successfully.');

        return redirect()->route('servers.logs.remote', ['server' => $server]);
    }
}
