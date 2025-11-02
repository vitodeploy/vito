<?php

namespace App\Http\Controllers;

use App\Actions\Server\CreateServer;
use App\Actions\Server\GetServers;
use App\Actions\Server\RebootServer;
use App\Actions\Server\TransferServer;
use App\Actions\Server\Update;
use App\Exceptions\SSHError;
use App\Helpers\QueryBuilder;
use App\Http\Resources\ServerLogResource;
use App\Http\Resources\ServerProviderResource;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use App\Models\ServerProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
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

        $servers = QueryBuilder::for($project->servers())
            ->searchableFields(['name', 'ip_address'])
            ->query()
            ->simplePaginate(config('web.pagination_size'), pageName: 'serversPage');

        return Inertia::render('servers/index', [
            'servers' => ServerResource::collection($servers),
            'public_key' => __('servers.create.public_key_text', ['public_key' => get_public_key_content()]),
            'server_providers' => ServerProviderResource::collection(ServerProvider::getByProjectId($project->id, user())->get()),
        ]);
    }

    #[Get('/json', name: 'servers.json')]
    public function json(Request $request): ResourceCollection
    {
        $project = user()->currentProject;

        $this->authorize('viewAny', [Server::class, $project]);

        $servers = app(GetServers::class)->get($project, $request->input(), 10);

        return ServerResource::collection($servers);
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
            'logs' => ServerLogResource::collection($server->logs()->latest()->simplePaginate(config('web.pagination_size'), pageName: 'logsPage')),
        ]);
    }

    #[Post('/{server}/switch', name: 'servers.switch')]
    public function switch(Server $server): RedirectResponse
    {
        $this->authorize('view', $server);

        return redirect()->route('servers.show', ['server' => $server->id]);
    }

    #[Patch('/{server}/status', name: 'servers.status')]
    public function status(Server $server): RedirectResponse
    {
        $this->authorize('view', $server);

        $server->checkConnection();

        $server->refresh();

        return back()
            ->with($server->status->getColor(), __('Server status is :status', [
                'status' => $server->status->getText(),
            ]));
    }

    #[Post('/{server}/reboot', name: 'servers.reboot')]
    public function reboot(Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        app(RebootServer::class)->reboot($server);

        return back()->with('success', 'Server is being rebooted.');
    }

    /**
     * @throws SSHError
     */
    #[Post('/{server}/check-for-updates', name: 'servers.check-for-updates')]
    public function checkForUpdates(Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        $server->checkForUpdates();

        return back()->with('info', 'Available updates: '.$server->refresh()->updates);
    }

    #[Post('/{server}/update', name: 'servers.update')]
    public function update(Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        app(Update::class)->update($server);

        return back()->with('info', 'Server is being updated. This may take a while.');
    }

    #[Post('/{server}/transfer', name: 'servers.transfer')]
    public function transfer(Server $server, Request $request): RedirectResponse
    {
        $this->authorize('delete', $server);

        $server = app(TransferServer::class)->transfer(user(), $server, $request->all());

        user()->update(['current_project_id' => $server->project_id]);

        return redirect()->route('server-settings', ['server' => $server->id])
            ->with('success', __('Server transferred successfully.'));
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
