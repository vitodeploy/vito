<?php

namespace App\Http\Livewire\Cronjobs;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateCronjob extends Component
{
    public Server $server;

    public string $user = '';

    public string $command;

    public string $frequency = '';

    public string $custom;

    public function create(): void
    {
        app(\App\Actions\CronJob\CreateCronJob::class)->create($this->server, $this->all());

        $this->dispatch('$refresh')->to(CronjobsList::class);

        $this->dispatch('created');
    }

    public function render(): View
    {
        return view('livewire.cronjobs.create-cronjob');
    }
}
