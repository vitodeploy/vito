<?php

namespace App\Http\Livewire\Profile;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class TwoFactorAuthentication extends Component
{
    public function render(): View
    {
        return view('livewire.profile.two-factor-authentication');
    }
}
