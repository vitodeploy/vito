<?php

namespace App\Http\Livewire\ServerProviders;

use App\Actions\ServerProvider\CreateServerProvider;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ConnectProvider extends Component
{
    public string $provider = '';

    public string $name;

    public string $token;

    public string $key;

    public string $secret;

    public function connect(): void
    {
        app(CreateServerProvider::class)->create(auth()->user(), $this->all());

        $this->emitTo(ProvidersList::class, '$refresh');

        $this->dispatchBrowserEvent('connected', true);
    }

    public function render(): View
    {
        if (request()->query('provider')) {
            $this->provider = request()->query('provider');
        }

        return view('livewire.server-providers.connect-provider', [
            'open' => ! is_null(request()->query('provider')),
        ]);
    }
}
