<?php

namespace App\Http\Livewire\Application;

use App\Exceptions\SourceControlIsNotConnected;
use App\Models\Site;
use App\Traits\HasToast;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Deploy extends Component
{
    use HasToast;
    use RefreshComponentOnBroadcast;

    public Site $site;

    public function deploy(): void
    {
        try {
            $this->site->deploy();

            $this->toast()->success(__('Deployment started!'));

            $this->dispatch('$refresh')->to(DeploymentsList::class);

            $this->dispatch('$refresh')->to(DeploymentScript::class);
        } catch (SourceControlIsNotConnected $e) {
            session()->flash('toast.type', 'error');
            session()->flash('toast.message', $e->getMessage());
            $this->redirect(route('source-controls'));
        }
    }

    public function render(): View
    {
        return view('livewire.application.deploy');
    }
}
