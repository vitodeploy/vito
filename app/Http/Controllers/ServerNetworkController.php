<?php

namespace App\Http\Controllers;

use App\Actions\ServerIp\ManageServerIp;
use App\Actions\ServerIp\RefreshServerIps;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Tables\Servers\ServerIpAddressTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/network')]
#[Middleware(['auth', 'has-project'])]
class ServerNetworkController extends Controller
{
    #[Get('/', name: 'servers.network')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [ServerIpAddress::class, $server]);

        return Inertia::render('server-network/index', [
            'ipAddresses' => ServerIpAddressTable::make($server->ipAddresses())->simplePaginate(),
            'interfaces' => $server->ipAddresses()
                ->whereNotNull('interface')
                ->distinct()
                ->orderBy('interface')
                ->pluck('interface'),
        ]);
    }

    /**
     * @throws SSHError
     */
    #[Post('/refresh', name: 'servers.network.refresh')]
    public function refresh(Server $server): RedirectResponse
    {
        $this->authorize('create', [ServerIpAddress::class, $server]);

        app(RefreshServerIps::class)->handle($server);

        return back()->with('success', 'IP addresses refreshed.');
    }

    #[Post('/ips', name: 'servers.network.ips.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [ServerIpAddress::class, $server]);

        app(ManageServerIp::class)->create($server, $request->only(['ip', 'ip_last', 'interface', 'prefix_length']));

        return back()->with('info', 'IP address is being configured.');
    }

    #[Post('/ips/{serverIpAddress}/primary', name: 'servers.network.ips.primary')]
    public function primary(Server $server, ServerIpAddress $serverIpAddress): RedirectResponse
    {
        if ($serverIpAddress->server_id !== $server->id) {
            abort(404);
        }

        $this->authorize('update', $serverIpAddress);

        app(ManageServerIp::class)->setPrimary($serverIpAddress);

        return back()->with('success', 'Primary IP address updated.');
    }

    #[Delete('/ips/{serverIpAddress}', name: 'servers.network.ips.destroy')]
    public function destroy(Server $server, ServerIpAddress $serverIpAddress): RedirectResponse
    {
        if ($serverIpAddress->server_id !== $server->id) {
            abort(404);
        }

        $this->authorize('delete', $serverIpAddress);

        app(ManageServerIp::class)->delete($serverIpAddress);

        return back()->with('info', 'IP address is being removed.');
    }
}
