<?php

namespace App\Http\Livewire\Application;

use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LaravelApp extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public function render(): View
    {
        return view('livewire.application.laravel-app');
    }
}
