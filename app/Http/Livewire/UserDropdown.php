<?php

namespace App\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class UserDropdown extends Component
{
    protected $listeners = [
        '$refresh',
    ];

    public function render(): View
    {
        return view('livewire.user-dropdown');
    }
}
