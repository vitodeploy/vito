<?php

namespace App\Http\Controllers;

use App\Actions\Server\CreateServer;
use App\Http\Resources\ServerLogResource;
use App\Http\Resources\ServerProviderResource;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use App\Models\ServerProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\ResponseFactory;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers')]
#[Middleware(['auth', 'has-project'])]
class ServerController extends Controller
{
    #[Get('/', name: 'servers')]
    public function index(): Response|ResponseFactory
    {
        $project = user()->currentProject;

        $this->authorize('viewAny', [Server::class, $project]);

        $servers = $project->servers()->simplePaginate(config('web.pagination_size'));

        return inertia('servers/index', [
            'servers' => ServerResource::collection($servers),
            'public_key' => __('servers.create.public_key_text', ['public_key' => get_public_key_content()]),
            'server_providers' => ServerProviderResource::collection(ServerProvider::getByProjectId($project->id)->get()),
        ]);
    }

    #[Post('/', name: 'servers.store')]
    public function store(Request $request): RedirectResponse
    {
        $project = user()->currentProject;

        $this->authorize('create', [Server::class, $project]);

        $server = app(CreateServer::class)->create(user(), $project, $request->all());

        return redirect()->route('servers.show', ['server' => $server->id]);
    }

    #[Get('/{server}', name: 'servers.show')]
    public function show(Server $server): Response|ResponseFactory
    {
        $this->authorize('view', $server);

        return inertia('servers/show', [
            'server' => ServerResource::make($server),
            'logs' => ServerLogResource::collection($server->logs()->latest()->paginate(config('web.pagination_size')))
        ]);
    }

    #[Post('/{server}/switch', name: 'servers.switch')]
    public function switch(Server $server): RedirectResponse
    {
        $this->authorize('view', $server);

        return redirect()->route('servers.show', ['server' => $server->id]);
    }
}
