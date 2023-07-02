<?php

namespace App\Http\Livewire\Firewall;

use App\Models\FirewallRule;
use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FirewallRulesList extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public int $deleteId;

    public function delete(): void
    {
        $rule = FirewallRule::query()->findOrFail($this->deleteId);

        $rule->removeFromServer();

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('confirmed', true);
    }

    public function render(): View
    {
        return view('livewire.firewall.firewall-rules-list', [
            'rules' => $this->server->firewallRules,
        ]);
    }
}
