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

    public string $host;

    public string $port;

    public string $path = '/';

    public string $username;

    public string $password;

    public int $ssl = 1;

    public int $passive = 0;

    public function connect(): void
    {
        app(CreateStorageProvider::class)->create(auth()->user(), $this->all());

        $this->dispatch('$refresh')->to(ProvidersList::class);

        $this->dispatch('connected');
    }

    public function render(): View
    {
        return view('livewire.storage-providers.connect-provider');
    }
}
