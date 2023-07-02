<?php

namespace App\Http\Livewire\Application;

use App\Actions\Site\UpdateDeploymentScript;
use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DeploymentScript extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public string $script;

    public function mount(): void
    {
        $this->script = $this->site->deploymentScript->content;
    }

    public function save(): void
    {
        app(UpdateDeploymentScript::class)->update($this->site, $this->all());

        session()->flash('status', 'script-updated');

        $this->emit(Deploy::class, '$refresh');
    }

    public function render(): View
    {
        return view('livewire.application.deployment-script');
    }
}
