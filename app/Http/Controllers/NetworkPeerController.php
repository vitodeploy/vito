<?php

namespace App\Http\Controllers;

use App\Actions\Network\ConcealNetworkPeerKey;
use App\Actions\Network\CreateNetworkPeer;
use App\Actions\Network\DeleteNetworkPeer;
use App\Actions\Network\GetNetworkPeerConfig;
use App\Actions\Network\RegenerateNetworkPeerKeys;
use App\Actions\Network\UpdateNetworkPeer;
use App\Enums\NetworkType;
use App\Models\Network;
use App\Models\NetworkPeer;
use App\Tables\Networks\NetworkPeerTable;
use Illuminate\Http\JsonResponse;
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

#[Prefix('networks/{network}/peers')]
#[Middleware(['auth', 'has-project'])]
class NetworkPeerController extends Controller
{
    #[Get('/', name: 'networks.peers')]
    public function index(Network $network): Response
    {
        $this->authorize('view', $network);
        abort_unless($network->type === NetworkType::WIREGUARD, 404);

        return Inertia::render('networks/peers', [
            'peers' => NetworkPeerTable::make($network->peers())->identifier('peers')->simplePaginate(),
        ]);
    }

    #[Post('/', name: 'networks.peers.store')]
    public function store(Request $request, Network $network): RedirectResponse
    {
        $this->authorize('update', $network);
        abort_unless($network->type === NetworkType::WIREGUARD, 404);

        app(CreateNetworkPeer::class)->create($network, $request->all());

        return back()->with('info', 'Peer is being added to the network.');
    }

    #[Get('/{networkPeer}/config', name: 'networks.peers.config')]
    public function config(Network $network, NetworkPeer $networkPeer): JsonResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkPeer);

        return response()->json(app(GetNetworkPeerConfig::class)->config($networkPeer))
            ->header('Cache-Control', 'no-store');
    }

    #[Post('/{networkPeer}/conceal', name: 'networks.peers.conceal')]
    public function conceal(Network $network, NetworkPeer $networkPeer): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkPeer);

        app(ConcealNetworkPeerKey::class)->conceal($networkPeer);

        return back();
    }

    #[Post('/{networkPeer}/regenerate', name: 'networks.peers.regenerate')]
    public function regenerate(Request $request, Network $network, NetworkPeer $networkPeer): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkPeer);

        app(RegenerateNetworkPeerKeys::class)->regenerate($networkPeer, $request->all());

        return back()->with('info', 'Peer keys are being regenerated.');
    }

    #[Put('/{networkPeer}', name: 'networks.peers.update')]
    public function update(Request $request, Network $network, NetworkPeer $networkPeer): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkPeer);

        app(UpdateNetworkPeer::class)->update($networkPeer, $request->all());

        return back()->with('success', 'Peer updated.');
    }

    #[Delete('/{networkPeer}', name: 'networks.peers.destroy')]
    public function destroy(Network $network, NetworkPeer $networkPeer): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkPeer);

        app(DeleteNetworkPeer::class)->delete($networkPeer);

        return back()->with('info', 'Peer is being removed from the network.');
    }

    private function ensureBelongsToNetwork(Network $network, NetworkPeer $networkPeer): void
    {
        abort_unless($networkPeer->network_id === $network->id, 404);
    }
}
