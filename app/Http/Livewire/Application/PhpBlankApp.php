<?php

namespace App\Http\Livewire\Application;

use App\Models\Site;
use App\Traits\RefreshComponentOnBroadcast;
use Livewire\Component;

class PhpBlankApp extends Component
{
    use RefreshComponentOnBroadcast;

    public Site $site;

    public function render()
    {
        return view('livewire.application.php-blank-app');
    }
}
