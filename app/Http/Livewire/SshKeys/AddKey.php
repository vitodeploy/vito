<?php

namespace App\Http\Livewire\SshKeys;

use App\Actions\SshKey\CreateSshKey;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddKey extends Component
{
    public string $name;

    public string $public_key;

    public function add(): void
    {
        app(CreateSshKey::class)->create(
            auth()->user(),
            $this->all()
        );

        $this->dispatch('$refresh')->to(KeysList::class);

        $this->dispatch('added');
    }

    public function render(): View
    {
        return view('livewire.ssh-keys.add-key');
    }
}
