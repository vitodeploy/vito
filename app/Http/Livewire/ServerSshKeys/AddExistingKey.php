<?php

namespace App\Http\Livewire\ServerSshKeys;

use App\Models\Server;
use App\Models\SshKey;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddExistingKey extends Component
{
    public Server $server;

    public string $key_id = '';

    public function add(): void
    {
        $key = SshKey::query()->findOrFail($this->all()['key_id']);

        $key->deployTo($this->server);

        $this->dispatch('$refresh')->to(ServerKeysList::class);

        $this->dispatch('added');
    }

    public function render(): View
    {
        return view('livewire.server-ssh-keys.add-existing-key', [
            'keys' => SshKey::all(),
        ]);
    }
}
