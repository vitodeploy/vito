<?php

namespace App\Http\Controllers;

use App\Actions\Server\CreateServer;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use App\Models\ServerProvider;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ServerController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $servers = $user->currentProject->servers()->orderByDesc('created_at')->get();

        return view('servers.index', compact('servers'));
    }

    public function create(Request $request): View
    {
        $provider = $request->query('provider', old('provider', \App\Enums\ServerProvider::CUSTOM));
        $serverProviders = ServerProvider::query()->where('provider', $provider)->get();

        return view('servers.create', [
            'serverProviders' => $serverProviders,
            'provider' => $provider,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request): HtmxResponse
    {
        $server = app(CreateServer::class)->create(
            $request->user(),
            $request->input()
        );

        Toast::success('Server created successfully.');

        return htmx()->redirect(route('servers.show', ['server' => $server]));
    }

    public function show(Server $server): View
    {
        return view('servers.show', [
            'server' => $server,
            'logs' => $server->logs()->latest()->limit(10)->get(),
        ]);
    }

    public function delete(Server $server): RedirectResponse
    {
        $server->delete();

        Toast::success('Server deleted successfully.');

        return redirect()->route('servers');
    }
}
