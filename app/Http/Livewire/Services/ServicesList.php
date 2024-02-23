<?php

namespace App\Http\Livewire\Services;

use App\Models\Server;
use App\Models\Service;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ServicesList extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public function stop(int $id): void
    {
        /** @var Service $service */
        $service = $this->server->services()->where('id', $id)->firstOrFail();

        $service->stop();

        $this->refreshComponent([]);
    }

    public function start(int $id): void
    {
        /** @var Service $service */
        $service = $this->server->services()->where('id', $id)->firstOrFail();

        $service->start();

        $this->refreshComponent([]);
    }

    public function restart(int $id): void
    {
        /** @var Service $service */
        $service = $this->server->services()->where('id', $id)->firstOrFail();

        $service->restart();

        $this->refreshComponent([]);
    }

    public function uninstall(int $id): void
    {
        /** @var Service $service */
        $service = $this->server->services()->where('id', $id)->firstOrFail();

        $service->uninstall();

        $this->refreshComponent([]);
    }

    public function render(): View
    {
        return view('livewire.services.services-list', [
            'services' => $this->server->services,
        ]);
    }
}
