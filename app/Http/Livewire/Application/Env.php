<?php

namespace App\Http\Livewire\Application;

use App\Actions\Site\UpdateEnv;
use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Env extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public string $env = 'Loading...';

    public function loadEnv(): void
    {
        $this->env = $this->site->getEnv();
    }

    public function save(): void
    {
        app(UpdateEnv::class)->update($this->site, $this->all());

        session()->flash('status', 'updating-env');

        $this->dispatch('$refresh')->to(Deploy::class);
    }

    public function render(): View
    {
        return view('livewire.application.env');
    }
}
