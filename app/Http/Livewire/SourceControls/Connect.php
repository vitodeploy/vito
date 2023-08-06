<?php

namespace App\Http\Livewire\SourceControls;

use App\Actions\SourceControl\ConnectSourceControl;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Connect extends Component
{
    public string $provider = '';

    public string $name;

    public string $token;

    public string $url;

    public function connect(): void
    {
        app(ConnectSourceControl::class)->connect($this->all());

        $this->emitTo(SourceControlsList::class, '$refresh');

        $this->dispatchBrowserEvent('connected', true);
    }

    public function render(): View
    {
        if (request()->query('provider')) {
            $this->provider = request()->query('provider');
        }

        return view('livewire.source-controls.connect', [
            'open' => ! is_null(request()->query('provider')),
        ]);
    }
}
