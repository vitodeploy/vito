<?php

namespace App\Http\Livewire\Firewall;

use App\Actions\FirewallRule\CreateRule;
use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateFirewallRule extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public string $type = 'allow';

    public string $protocol = 'tcp';

    public string $port;

    public string $source = '0.0.0.0';

    public string $mask = '';

    public function create(): void
    {
        app(CreateRule::class)->create($this->server, $this->all());

        $this->dispatch('$refresh')->to(FirewallRulesList::class);

        $this->dispatch('created');
    }

    public function render(): View
    {
        return view('livewire.firewall.create-firewall-rule');
    }
}
