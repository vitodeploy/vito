<?php

namespace App\Http\Livewire\Application;

use App\Models\Site;
use App\Traits\HasCustomPaginationView;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DeploymentsList extends Component
{
    use HasCustomPaginationView;
    use RefreshComponentOnBroadcast;

    public Site $site;

    public string $logContent;

    public function showLog(int $id): void
    {
        $deployment = $this->site->deployments()->findOrFail($id);
        $this->logContent = $deployment->log->content;

        $this->dispatch('open-modal', modal: 'show-log');
    }

    public function render(): View
    {
        return view('livewire.application.deployments-list', [
            'deployments' => $this->site->deployments()->latest()->simplePaginate(10),
        ]);
    }
}
