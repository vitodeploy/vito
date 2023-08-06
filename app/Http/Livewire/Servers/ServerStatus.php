<?php

namespace App\Http\Livewire\Servers;

use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ServerStatus extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public function render(): View
    {
        return view('livewire.servers.server-status');
    }
}
