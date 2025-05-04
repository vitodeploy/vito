<?php

namespace App\Http\Controllers;

use App\Actions\Server\CreateServer;
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
            'providers' => config('core.server_providers'),
            'public_key' => __('servers.create.public_key_text', ['public_key' => get_public_key_content()]),
            'operating_systems' => config('core.operating_systems'),
            'server_providers' => ServerProviderResource::collection(ServerProvider::getByProjectId($project->id)->get()),
        ]);
    }

    #[Post('/', name: 'servers.store')]
    public function store(Request $request): RedirectResponse
    {
        $project = user()->currentProject;

        $this->authorize('create', [Server::class, $project]);

        $server = app(CreateServer::class)->create(user(), $project, $request->all());

        return redirect()->route('servers');
    }
}
