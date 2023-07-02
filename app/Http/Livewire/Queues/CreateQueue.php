<?php

namespace App\Http\Livewire\Queues;

use App\Models\Site;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateQueue extends Component
{
    public Site $site;

    public string $command;

    public string $user = '';

    public int $auto_start = 1;

    public int $auto_restart = 1;

    public int $numprocs;

    public function create(): void
    {
        app(\App\Actions\Queue\CreateQueue::class)->create($this->site, $this->all());

        $this->emitTo(QueuesList::class, '$refresh');

        $this->dispatchBrowserEvent('created', true);
    }

    public function render(): View
    {
        return view('livewire.queues.create-queue');
    }
}
