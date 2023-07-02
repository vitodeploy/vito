<?php

namespace App\Http\Livewire\Queues;

use App\Models\Queue;
use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class QueuesList extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public int $deleteId;

    public function delete(): void
    {
        $queue = $this->site->queues()->findOrFail($this->deleteId);

        $queue->remove();

        $this->refreshComponent([]);

        $this->dispatchBrowserEvent('confirmed', true);
    }

    public function start(Queue $queue): void
    {
        $queue->start();

        $this->refreshComponent([]);
    }

    public function restart(Queue $queue): void
    {
        $queue->restart();

        $this->refreshComponent([]);
    }

    public function stop(Queue $queue): void
    {
        $queue->stop();

        $this->refreshComponent([]);
    }

    public function render(): View
    {
        return view('livewire.queues.queues-list', [
            'queues' => $this->site->queues,
        ]);
    }
}
