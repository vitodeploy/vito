<?php

namespace App\Http\Livewire\Servers;

use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ShowServer extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public function refreshComponent(array $data): void
    {
        if (isset($data['type']) && $data['type'] == 'install-server-finished') {
            $this->redirect(route('servers.show', ['server' => $this->server]));

            return;
        }

        $this->dispatch('refreshComponent');
    }

    public function render(): View
    {
        return view('livewire.servers.show-server');
    }
}
