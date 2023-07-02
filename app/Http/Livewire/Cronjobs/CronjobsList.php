<?php

namespace App\Http\Livewire\Cronjobs;

use App\Models\Server;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CronjobsList extends Component
{
    use RefreshComponentOnBroadcast;

    public Server $server;

    public int $deleteId;

    public function delete(): void
    {
        $cronjob = $this->server->cronJobs()->where('id', $this->deleteId)->firstOrFail();

        $cronjob->removeFromServer();

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('confirmed', true);
    }

    public function render(): View
    {
        return view('livewire.cronjobs.cronjobs-list', [
            'cronjobs' => $this->server->cronJobs,
        ]);
    }
}
