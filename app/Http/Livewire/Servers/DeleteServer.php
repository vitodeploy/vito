<?php

namespace App\Http\Livewire\Servers;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DeleteServer extends Component
{
    public Server $server;

    public function mount(Server $server): void
    {
        $this->server = $server;
    }

    public function delete(): void
    {
        $this->server->delete();

        $this->redirect(route('servers'));
    }

    public function render(): View
    {
        return view('livewire.servers.delete-server');
    }
}
