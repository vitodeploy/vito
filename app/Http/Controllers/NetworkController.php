<?php

namespace App\Http\Controllers;

use App\Actions\Network\CreateNetwork;
use App\Actions\Network\DeleteNetwork;
use App\Actions\Network\SyncNetwork;
use App\Actions\Network\UpdateNetwork;
use App\Enums\IpAddressType;
use App\Enums\NetworkType;
use App\Helpers\QueryBuilder;
use App\Http\Resources\NetworkMemberIpResource;
use App\Http\Resources\NetworkServerOptionResource;
use App\Http\Resources\ServerLogResource;
use App\Jobs\Network\SyncProviderNetworksJob;
use App\Models\Network;
use App\Models\Project;
use App\Tables\Networks\NetworkServerTable;
use App\Tables\NetworkTable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('networks')]
#[Middleware(['auth', 'has-project'])]
class NetworkController extends Controller
{
    #[Get('/', name: 'networks')]
    public function index(): Response
    {
        $project = user()->currentProject;

        $this->authorize('viewAny', [Network::class, $project]);

        return Inertia::render('networks/index', [
            'networks' => NetworkTable::make($project->networks())->simplePaginate(),
            'servers' => $this->serversPayload($project),
        ]);
    }

    #[Post('/', name: 'networks.store')]
    public function store(Request $request): RedirectResponse
    {
        $project = user()->currentProject;

        $this->authorize('create', [Network::class, $project]);

        $network = app(CreateNetwork::class)->create($project, $request->all());

        return redirect()->route('networks.show', $network->id)
            ->with('info', 'Network is being created.');
    }

    #[Get('/{network}', name: 'networks.show')]
    public function show(Network $network): Response
    {
        $this->authorize('view', $network);

        $network->loadCount(['servers', 'peers', 'firewallRules']);

        return Inertia::render('networks/show', [
            'stats' => [
                'servers' => $network->servers_count,
                'peers' => $network->peers_count,
                'firewall_rules' => $network->firewall_rules_count,
            ],
            'logs' => ServerLogResource::collection($this->logsQuery($network)),
        ]);
    }

    /**
     * @return Paginator<int, Model>
     */
    private function logsQuery(Network $network): Paginator
    {
        return QueryBuilder::for($network->serverLogs()->with('server'))
            ->searchableFields(['name'])
            ->sortable('created_at', 'desc')
            ->query()
            ->simplePaginate(config('web.pagination_size'));
    }

    #[Get('/{network}/servers', name: 'networks.servers')]
    public function servers(Network $network): Response
    {
        $this->authorize('view', $network);

        $memberServerIds = $network->servers()->pluck('server_id')->all();

        return Inertia::render('networks/servers', [
            'members' => NetworkServerTable::make($network->servers())->identifier('members')->simplePaginate(),
            'servers' => $this->serversPayload($network->project, $memberServerIds),
            'memberIps' => $network->type === NetworkType::CUSTOM ? $this->memberIpsPayload($network) : [],
        ]);
    }

    private function memberIpsPayload(Network $network): AnonymousResourceCollection
    {
        return NetworkMemberIpResource::collection(
            $network->servers()
                ->with(['server', 'server.ipAddresses' => fn ($query) => $query->where('type', IpAddressType::PRIVATE)])
                ->get()
        );
    }

    #[Get('/{network}/logs', name: 'networks.logs')]
    public function logs(Network $network): Response
    {
        $this->authorize('view', $network);

        return Inertia::render('networks/logs', [
            'logs' => ServerLogResource::collection($this->logsQuery($network)),
        ]);
    }

    #[Get('/{network}/settings', name: 'networks.settings')]
    public function settings(Network $network): Response
    {
        $this->authorize('view', $network);

        return Inertia::render('networks/settings');
    }

    #[Put('/{network}', name: 'networks.update')]
    public function update(Request $request, Network $network): RedirectResponse
    {
        $this->authorize('update', $network);

        app(UpdateNetwork::class)->update($network, $request->all());

        return back()->with('success', 'Changes saved!');
    }

    #[Delete('/{network}', name: 'networks.destroy')]
    public function destroy(Network $network): RedirectResponse
    {
        $this->authorize('delete', $network);

        app(DeleteNetwork::class)->delete($network);

        return redirect()->route('networks')->with('success', 'Network deleted!');
    }

    #[Post('/sync', name: 'networks.sync-providers')]
    public function syncProviders(): RedirectResponse
    {
        $project = user()->currentProject;

        $this->authorize('create', [Network::class, $project]);

        if (! SyncProviderNetworksJob::dispatchUnlessRecent($project)) {
            return back()->with('info', 'A provider sync is already in progress.');
        }

        return back()->with('info', 'Syncing networks from your cloud providers.');
    }

    #[Post('/{network}/sync', name: 'networks.sync')]
    public function sync(Network $network): RedirectResponse
    {
        $this->authorize('update', $network);

        app(SyncNetwork::class)->network($network);

        return back()->with('info', 'Network is being synced.');
    }

    /**
     * @param  array<int, int>  $excludeServerIds
     */
    private function serversPayload(Project $project, array $excludeServerIds = []): AnonymousResourceCollection
    {
        return NetworkServerOptionResource::collection(
            $project->servers()
                ->when($excludeServerIds !== [], fn ($query) => $query->whereNotIn('id', $excludeServerIds))
                ->with(['ipAddresses' => fn ($query) => $query->where('type', IpAddressType::PRIVATE)])
                ->get()
        );
    }
}
