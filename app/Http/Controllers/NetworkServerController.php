<?php

namespace App\Http\Controllers;

use App\Actions\Network\AddServersToNetwork;
use App\Actions\Network\RemoveServerFromNetwork;
use App\Actions\Network\SyncNetwork;
use App\Actions\Network\UpdateNetworkServerIp;
use App\Enums\NetworkType;
use App\Models\Network;
use App\Models\NetworkServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('networks/{network}/servers')]
#[Middleware(['auth', 'has-project'])]
class NetworkServerController extends Controller
{
    #[Post('/', name: 'networks.servers.store')]
    public function store(Request $request, Network $network): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureNotProviderManaged($network);

        $movedPort = app(AddServersToNetwork::class)->add($network, $request->all());

        if ($movedPort !== null && $network->peers()->exists()) {
            return back()->with('warning', 'Servers are being added. The listen port moved to '.$movedPort.', so every peer must download its config again to reconnect.');
        }

        return back()->with('info', 'Servers are being added to the network.');
    }

    #[Post('/{networkServer}/sync', name: 'networks.servers.sync')]
    public function sync(Network $network, NetworkServer $networkServer): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkServer);
        $this->ensureNotProviderManaged($network);

        app(SyncNetwork::class)->member($networkServer);

        return back()->with('info', 'Server configuration is being regenerated.');
    }

    #[Put('/{networkServer}', name: 'networks.servers.update')]
    public function update(Request $request, Network $network, NetworkServer $networkServer): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkServer);
        abort_unless($network->type === NetworkType::CUSTOM, 404);

        app(UpdateNetworkServerIp::class)->update($networkServer, $request->only('server_ip_address_id'));

        return back()->with('success', 'IP address updated.');
    }

    #[Delete('/{networkServer}', name: 'networks.servers.destroy')]
    public function destroy(Network $network, NetworkServer $networkServer): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkServer);
        $this->ensureNotProviderManaged($network);

        app(RemoveServerFromNetwork::class)->remove($networkServer);

        return back()->with('info', 'Server is being removed from the network.');
    }

    private function ensureBelongsToNetwork(Network $network, NetworkServer $networkServer): void
    {
        abort_unless($networkServer->network_id === $network->id, 404);
    }

    private function ensureNotProviderManaged(Network $network): void
    {
        abort_if($network->type === NetworkType::PROVIDER, 404);
    }
}
