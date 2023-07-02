<?php

namespace App\Http\Livewire\ServerSettings;

use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ServerDetails extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public function render(): View
    {
        return view('livewire.server-settings.server-details');
    }
}
