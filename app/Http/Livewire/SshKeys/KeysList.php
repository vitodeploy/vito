<?php

namespace App\Http\Livewire\SshKeys;

use App\Models\SshKey;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class KeysList extends Component
{
    use RefreshComponentOnBroadcast;

    public int $deleteId;

    protected $listeners = [
        '$refresh',
    ];

    public function delete(): void
    {
        $key = SshKey::query()->findOrFail($this->deleteId);

        $key->delete();

        $this->refreshComponent([]);

        $this->dispatch('confirmed');
    }

    public function render(): View
    {
        return view('livewire.ssh-keys.keys-list', [
            'keys' => SshKey::query()->latest()->get(),
        ]);
    }
}
