<?php

namespace App\Http\Livewire\Application;

use App\Exceptions\SourceControlIsNotConnected;
use App\Models\Site;
use App\Traits\HasToast;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class AutoDeployment extends Component
{
    use RefreshComponentOnBroadcast;
    use HasToast;

    public Site $site;

    /**
     * @throws Throwable
     */
    public function enable(): void
    {
        if (! $this->site->auto_deployment) {
            try {
                $this->site->enableAutoDeployment();

                $this->site->refresh();

                $this->toast()->success(__('Auto deployment has been enabled.'));
            } catch (SourceControlIsNotConnected) {
                $this->toast()->error(__('Source control is not connected. Check site\'s settings.'));
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function disable(): void
    {
        if ($this->site->auto_deployment) {
            try {
                $this->site->disableAutoDeployment();

                $this->site->refresh();

                $this->toast()->success(__('Auto deployment has been disabled.'));
            } catch (SourceControlIsNotConnected) {
                $this->toast()->error(__('Source control is not connected. Check site\'s settings.'));
            }
        }
    }

    public function render(): View
    {
        return view('livewire.application.auto-deployment');
    }
}
