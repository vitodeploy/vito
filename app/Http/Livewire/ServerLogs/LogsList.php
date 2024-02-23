<?php

namespace App\Http\Livewire\ServerLogs;

use App\Models\Server;
use App\Models\Site;
use App\Traits\HasCustomPaginationView;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LogsList extends Component
{
    use HasCustomPaginationView;
    use RefreshComponentOnBroadcast;

    public ?int $count = null;

    public Server $server;

    public ?Site $site = null;

    public string $logContent;

    public function showLog(int $id): void
    {
        $log = $this->server->logs()->findOrFail($id);
        $this->logContent = $log->content;

        $this->dispatch('open-modal', 'show-log');
    }

    public function render(): View
    {
        if ($this->site) {
            return $this->renderSite();
        }

        if ($this->count) {
            $logs = $this->server->logs()->latest()->take(10)->get();
        } else {
            $logs = $this->server->logs()->latest()->simplePaginate(10);
        }

        return view('livewire.server-logs.logs-list', compact('logs'));
    }

    private function renderSite(): View
    {
        if ($this->count) {
            $logs = $this->site->logs()->latest()->take(10)->get();
        } else {
            $logs = $this->site->logs()->latest()->simplePaginate(10);
        }

        return view('livewire.server-logs.logs-list', compact('logs'));
    }
}
