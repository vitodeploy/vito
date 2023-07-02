<?php

namespace App\Http\Livewire\Sites;

use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SitesList extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public function refreshComponent(array $data): void
    {
        if (isset($data['type']) && $data['type'] == 'install-site-finished') {
            $this->redirect(
                route('servers.sites.show', [
                    'server' => $this->server,
                    'site' => $data['data']['site']['id'],
                ])
            );

            return;
        }

        $this->emit('refreshComponent');
    }

    public function render(): View
    {
        return view('livewire.sites.sites-list', [
            'sites' => $this->server->sites()->latest()->get(),
        ]);
    }
}
