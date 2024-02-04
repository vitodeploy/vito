<?php

namespace App\Http\Livewire\StorageProviders;

use App\Models\StorageProvider;
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
        $provider = StorageProvider::query()->findOrFail($this->deleteId);

        $provider->delete();

        $this->refreshComponent([]);

        $this->dispatch('confirmed');
    }

    public function render(): View
    {
        return view('livewire.storage-providers.providers-list', [
            'providers' => StorageProvider::query()->latest()->get(),
        ]);
    }
}
