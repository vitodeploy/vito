<?php

namespace App\Http\Livewire\Ssl;

use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateSsl extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public string $type = '';

    public string $certificate;

    public string $private;

    public function create(): void
    {
        app(\App\Actions\SSL\CreateSSL::class)->create($this->site, $this->all());

        $this->dispatch('$refresh')->to(SslsList::class);

        $this->dispatch('created');
    }

    public function render(): View
    {
        return view('livewire.ssl.create-ssl');
    }
}
