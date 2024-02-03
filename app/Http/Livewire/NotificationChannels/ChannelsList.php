<?php

namespace App\Http\Livewire\NotificationChannels;

use App\Models\NotificationChannel;
use App\Traits\RefreshComponentOnBroadcast;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ChannelsList extends Component
{
    use RefreshComponentOnBroadcast;

    public int $deleteId;

    protected $listeners = [
        '$refresh',
    ];

    public function delete(): void
    {
        $channel = NotificationChannel::query()->findOrFail($this->deleteId);

        $channel->delete();

        $this->refreshComponent([]);

        $this->dispatch('confirmed');
    }

    public function render(): View
    {
        return view('livewire.notification-channels.channels-list', [
            'channels' => NotificationChannel::query()->latest()->get(),
        ]);
    }
}
