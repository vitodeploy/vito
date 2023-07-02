<?php

namespace App\Http\Livewire\ServerSshKeys;

use App\Models\Server;
use App\Models\SshKey;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ServerKeysList extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public int $deleteId;

    protected $listeners = [
        '$refresh',
    ];

    public function delete(): void
    {
        $key = SshKey::query()->findOrFail($this->deleteId);

        $key->deleteFrom($this->server);

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('confirmed', true);
    }

    public function render(): View
    {
        return view('livewire.server-ssh-keys.server-keys-list', [
            'keys' => $this->server->sshKeys,
        ]);
    }
}
