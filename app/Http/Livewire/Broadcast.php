<?php

namespace App\Http\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Broadcast extends Component
{
    public function render(): View
    {
        $event = Cache::get('broadcast');
        if ($event) {
            Cache::forget('broadcast');
            $this->dispatch('broadcast', $event);
        }

        return view('livewire.broadcast');
    }
}
