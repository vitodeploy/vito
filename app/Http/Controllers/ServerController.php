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
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers')]
#[Middleware(['auth', 'has-project'])]
class ServerController extends Controller
{
    #[Get('/', name: 'servers')]
    public function index(): Response
    {
        $project = user()->currentProject;

        $this->authorize('viewAny', [Server::class, $project]);

        $servers = $project->servers()->simplePaginate(config('web.pagination_size'));

        return Inertia::render('servers/index', [
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
    public function show(Server $server): Response
    {
        $this->authorize('view', $server);

        return Inertia::render('servers/show', [
            'logs' => ServerLogResource::collection($server->logs()->latest()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Post('/{server}/switch', name: 'servers.switch')]
    public function switch(Server $server): RedirectResponse
    {
        $this->authorize('view', $server);

        $previousUrl = URL::previous();
        $previousRequest = Request::create($previousUrl);
        $previousRoute = app('router')->getRoutes()->match($previousRequest);

        if ($previousRoute->hasParameter('server')) {
            if (count($previousRoute->parameters()) > 1) {
                return redirect()->route('servers.show', ['server' => $server->id]);
            }

            return redirect()->route($previousRoute->getName(), ['server' => $server]);
        }

        return redirect()->route('servers.show', ['server' => $server->id]);
    }

    #[Delete('/{server}', name: 'servers.destroy')]
    public function destroy(Server $server, Request $request): RedirectResponse
    {
        $this->authorize('delete', $server);

        $this->validate($request, [
            'name' => [
                'required',
                Rule::in([$server->name]),
            ],
        ]);

        $server->delete();

        return redirect()->route('servers')
            ->with('success', __('Server deleted successfully.'));
    }
}
