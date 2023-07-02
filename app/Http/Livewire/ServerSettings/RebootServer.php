<?php

namespace App\Http\Livewire\ServerSettings;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RebootServer extends Component
{
    public Server $server;

    public function reboot(): void
    {
        $this->server->reboot();

        session()->flash('status', 'rebooting-server');
    }

    public function render(): View
    {
        return view('livewire.server-settings.reboot-server');
    }
}
