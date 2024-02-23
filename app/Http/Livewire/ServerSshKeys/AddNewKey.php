<?php

namespace App\Http\Livewire\ServerSshKeys;

use App\Actions\SshKey\CreateSshKey;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddNewKey extends Component
{
    public Server $server;

    public string $name;

    public string $public_key;

    public function add(): void
    {
        $key = app(CreateSshKey::class)->create(
            auth()->user(),
            $this->all()
        );

        $key->deployTo($this->server);

        $this->dispatch('$refresh')->to(ServerKeysList::class);

        $this->dispatch('added');
    }

    public function render(): View
    {
        return view('livewire.server-ssh-keys.add-new-key');
    }
}
