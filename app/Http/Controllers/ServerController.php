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

        $this->authorize('viewAny', [Server::class, $user->currentProject]);

        $servers = $user->currentProject->servers()->orderByDesc('created_at')->get();

        return view('servers.index', compact('servers'));
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = auth()->user();

        $this->authorize('create', [Server::class, $user->currentProject]);

        $provider = $request->query('provider', old('provider', \App\Enums\ServerProvider::CUSTOM));
        $serverProviders = ServerProvider::getByProjectId(auth()->user()->current_project_id)
            ->where('provider', $provider)
            ->get();

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
        /** @var User $user */
        $user = auth()->user();

        $this->authorize('create', [Server::class, $user->currentProject]);

        $server = app(CreateServer::class)->create(
            $user,
            $request->input()
        );

        Toast::success('Server created successfully.');

        return htmx()->redirect(route('servers.show', ['server' => $server]));
    }

    public function show(Server $server): View
    {
        $this->authorize('view', $server);

        return view('servers.show', [
            'server' => $server,
        ]);
    }

    public function delete(Server $server): RedirectResponse
    {
        $this->authorize('delete', $server);

        $server->delete();

        Toast::success('Server deleted successfully.');

        return redirect()->route('servers');
    }
}
