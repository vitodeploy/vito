<?php

namespace App\Http\Livewire\ServerProviders;

use App\Models\ServerProvider;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProvidersList extends Component
{
    use RefreshComponentOnBroadcast;

    public int $deleteId;

    protected $listeners = [
        '$refresh',
    ];

    public function delete(): void
    {
        $provider = ServerProvider::query()->findOrFail($this->deleteId);

        $provider->delete();

        $this->refreshComponent([]);

        $this->dispatch('confirmed');
    }

    public function render(): View
    {
        return view('livewire.server-providers.providers-list', [
            'providers' => ServerProvider::query()->latest()->get(),
        ]);
    }
}
