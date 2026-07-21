<?php

namespace App\Http\Controllers;

use App\Actions\Network\CreateNetwork;
use App\Actions\Network\DeleteNetwork;
use App\Actions\Network\SyncNetwork;
use App\Actions\Network\UpdateNetwork;
use App\Enums\IpAddressType;
use App\Http\Resources\NetworkResource;
use App\Models\Network;
use App\Models\Project;
use App\Models\Server;
use App\Tables\Networks\NetworkFirewallRuleTable;
use App\Tables\Networks\NetworkServerTable;
use App\Tables\NetworkTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $memberServerIds = $network->servers()->pluck('server_id')->all();

        return Inertia::render('networks/show', [
            'network' => new NetworkResource($network->loadCount('servers')),
            'members' => NetworkServerTable::make($network->servers())->identifier('members')->simplePaginate(),
            'rules' => NetworkFirewallRuleTable::make($network->firewallRules())->identifier('rules')->simplePaginate(),
            'servers' => $this->serversPayload($network->project, $memberServerIds),
        ]);
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

    #[Post('/{network}/sync', name: 'networks.sync')]
    public function sync(Network $network): RedirectResponse
    {
        $this->authorize('update', $network);

        app(SyncNetwork::class)->network($network);

        return back()->with('info', 'Network is being synced.');
    }

    /**
     * @param  array<int, int>  $excludeServerIds
     * @return array<int, array<string, mixed>>
     */
    private function serversPayload(Project $project, array $excludeServerIds = []): array
    {
        return $project->servers()
            ->when($excludeServerIds !== [], fn ($query) => $query->whereNotIn('id', $excludeServerIds))
            ->with(['ipAddresses' => fn ($query) => $query->where('type', IpAddressType::PRIVATE)])
            ->get()
            ->map(fn (Server $server): array => [
                'id' => $server->id,
                'name' => $server->name,
                'is_ready' => $server->isReady(),
                'private_ips' => $server->ipAddresses->map(fn ($ip): array => [
                    'id' => $ip->id,
                    'ip' => $ip->ip,
                    'is_primary' => $ip->is_primary,
                ])->values(),
            ])
            ->all();
    }
}
