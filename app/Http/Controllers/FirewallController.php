<?php

namespace App\Http\Controllers;

use App\Actions\FirewallRule\ManageRule;
use App\Models\FirewallRule;
use App\Models\Server;
use App\Tables\Servers\FirewallRuleTable;
use App\Tables\Servers\ServerNetworkRuleTable;
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

#[Prefix('servers/{server}/firewall')]
#[Middleware(['auth', 'has-project'])]
class FirewallController extends Controller
{
    #[Get('/', name: 'firewall')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [FirewallRule::class, $server]);

        return Inertia::render('firewall/index', [
            'rules' => FirewallRuleTable::make($server->firewallRules())->simplePaginate(),
            'networkRules' => ServerNetworkRuleTable::make($server->networkRules())
                ->identifier('networkRules')
                ->simplePaginate(),
        ]);
    }

    #[Post('/', name: 'firewall.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [FirewallRule::class, $server]);

        app(ManageRule::class)->create($server, $request->all());

        return back()
            ->with('info', 'Firewall rule is being created.');
    }

    #[Put('/{firewallRule}', name: 'firewall.update')]
    public function update(Request $request, Server $server, FirewallRule $firewallRule): RedirectResponse
    {
        $this->authorize('update', $firewallRule);

        app(ManageRule::class)->update($firewallRule, $request->all());

        return back()
            ->with('info', 'Firewall rule is being updated.');
    }

    #[Delete('/{firewallRule}', name: 'firewall.destroy')]
    public function destroy(Server $server, FirewallRule $firewallRule): RedirectResponse
    {
        $this->authorize('delete', $firewallRule);

        app(ManageRule::class)->delete($firewallRule);

        return back()
            ->with('info', 'Firewall rule is being deleted.');
    }
}
