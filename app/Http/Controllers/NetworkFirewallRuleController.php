<?php

namespace App\Http\Controllers;

use App\Actions\Network\ManageNetworkFirewallRule;
use App\Models\Network;
use App\Models\NetworkFirewallRule;
use App\Tables\Networks\NetworkFirewallRuleTable;
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

#[Prefix('networks/{network}/firewall')]
#[Middleware(['auth', 'has-project'])]
class NetworkFirewallRuleController extends Controller
{
    #[Get('/', name: 'networks.firewall')]
    public function index(Network $network): Response
    {
        $this->authorize('view', $network);

        return Inertia::render('networks/firewall', [
            'rules' => NetworkFirewallRuleTable::make($network->firewallRules())->identifier('rules')->simplePaginate(),
        ]);
    }

    #[Post('/', name: 'networks.firewall.store')]
    public function store(Request $request, Network $network): RedirectResponse
    {
        $this->authorize('update', $network);

        app(ManageNetworkFirewallRule::class)->create($network, $request->all());

        return back()->with('info', 'Firewall rule is being created.');
    }

    #[Put('/{networkFirewallRule}', name: 'networks.firewall.update')]
    public function update(Request $request, Network $network, NetworkFirewallRule $networkFirewallRule): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkFirewallRule);

        app(ManageNetworkFirewallRule::class)->update($networkFirewallRule, $request->all());

        return back()->with('info', 'Firewall rule is being updated.');
    }

    #[Delete('/{networkFirewallRule}', name: 'networks.firewall.destroy')]
    public function destroy(Network $network, NetworkFirewallRule $networkFirewallRule): RedirectResponse
    {
        $this->authorize('update', $network);
        $this->ensureBelongsToNetwork($network, $networkFirewallRule);

        app(ManageNetworkFirewallRule::class)->delete($networkFirewallRule);

        return back()->with('info', 'Firewall rule is being deleted.');
    }

    private function ensureBelongsToNetwork(Network $network, NetworkFirewallRule $networkFirewallRule): void
    {
        abort_unless($networkFirewallRule->network_id === $network->id, 404);
    }
}
