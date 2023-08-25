<?php

namespace App\Http\Livewire\StorageProviders;

use App\Actions\StorageProvider\CreateStorageProvider;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ConnectProvider extends Component
{
    public string $provider = '';

    public string $name;

    public string $token;

    public function connect(): void
    {
        app(CreateStorageProvider::class)->create(auth()->user(), $this->all());

        $this->emitTo(ProvidersList::class, '$refresh');

        $this->dispatchBrowserEvent('connected', true);
    }

    public function render(): View
    {
        return view('livewire.storage-providers.connect-provider');
    }
}
