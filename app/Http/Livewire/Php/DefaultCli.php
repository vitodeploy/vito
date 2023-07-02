<?php

namespace App\Http\Livewire\Php;

use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DefaultCli extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public function change(string $version): void
    {
        $this->server->php($version)->handler()->setDefaultCli();

        $this->refreshComponent([]);
    }

    public function render(): View
    {
        return view('livewire.php.default-cli', [
            'defaultPHP' => $this->server->defaultService('php'),
            'phps' => $this->server->services()->where('type', 'php')->get(), //
        ]);
    }
}
