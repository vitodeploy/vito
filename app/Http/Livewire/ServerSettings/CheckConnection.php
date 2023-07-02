<?php

namespace App\Http\Livewire\ServerSettings;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CheckConnection extends Component
{
    public Server $server;

    public function check(): void
    {
        $this->server->checkConnection();

        session()->flash('status', 'checking-connection');
    }

    public function render(): View
    {
        return view('livewire.server-settings.check-connection');
    }
}
