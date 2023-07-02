<?php

namespace App\Http\Livewire\Servers;

use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ServersList extends Component
{
    use RefreshComponentOnBroadcast;

    public function render(): View
    {
        return view('livewire.servers.servers-list', [
            'servers' => Server::all(),
        ]);
    }
}
