<?php

namespace App\Web\Pages\Servers\Console\Widgets;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Console extends Component
{
    public Server $server;

    public function render(): View
    {
        return view('components.console', [
            'server' => $this->server,
        ]);
    }
}
