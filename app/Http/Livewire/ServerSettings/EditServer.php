<?php

namespace App\Http\Livewire\ServerSettings;

use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EditServer extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public string $name;

    public string $ip;

    public string $port;

    public function mount(): void
    {
        $this->name = $this->server->name;
        $this->ip = $this->server->ip;
        $this->port = $this->server->port;
    }

    public function update(): void
    {
        app(\App\Actions\Server\EditServer::class)->edit($this->server, $this->all());

        session()->flash('status', 'server-updated');
    }

    public function render(): View
    {
        return view('livewire.server-settings.edit-server');
    }
}
