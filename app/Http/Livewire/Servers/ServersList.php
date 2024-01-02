<?php

namespace App\Http\Livewire\Servers;

use App\Models\User;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ServersList extends Component
{
    use RefreshComponentOnBroadcast;

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $servers = $user->currentProject->servers()->orderByDesc('created_at')->get();

        return view('livewire.servers.servers-list', [
            'servers' => $servers,
        ]);
    }
}
