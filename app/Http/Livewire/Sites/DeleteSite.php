<?php

namespace App\Http\Livewire\Sites;

use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DeleteSite extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public function delete(): void
    {
        $this->site->remove();

        $this->redirect(route('servers.sites', ['server' => $this->site->server]));
    }

    public function render(): View
    {
        return view('livewire.sites.delete-site');
    }
}
