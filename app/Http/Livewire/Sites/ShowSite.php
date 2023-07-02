<?php

namespace App\Http\Livewire\Sites;

use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ShowSite extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public function render(): View
    {
        return view('livewire.sites.show-site');
    }
}
