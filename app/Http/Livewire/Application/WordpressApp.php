<?php

namespace App\Http\Livewire\Application;

use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WordpressApp extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public function render(): View
    {
        return view('livewire.application.wordpress-app');
    }
}
