<?php

namespace App\Http\Controllers;

use App\Actions\FirewallRule\CreateRule;
use App\Actions\FirewallRule\DeleteRule;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\FirewallRule;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FirewallController extends Controller
{
    public function index(Server $server): View
    {
        $this->authorize('manage', $server);

        return view('firewall.index', [
            'server' => $server,
            'rules' => $server->firewallRules,
        ]);
    }

    public function store(Server $server, Request $request): HtmxResponse
    {
        $this->authorize('manage', $server);

        app(CreateRule::class)->create($server, $request->input());

        Toast::success('Firewall rule created!');

        return htmx()->back();
    }

    public function destroy(Server $server, FirewallRule $firewallRule): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(DeleteRule::class)->delete($server, $firewallRule);

        Toast::success('Firewall rule deleted!');

        return back();
    }
}
