<?php

namespace App\Http\Livewire\NotificationChannels;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddChannel extends Component
{
    public string $provider = '';

    public string $label;

    public string $webhook_url;

    public string $email;

    public function add(): void
    {
        app(\App\Actions\NotificationChannels\AddChannel::class)->add(
            auth()->user(),
            $this->all()
        );

        $this->emitTo(ChannelsList::class, '$refresh');

        $this->dispatchBrowserEvent('added', true);
    }

    public function render(): View
    {
        return view('livewire.notification-channels.add-channel');
    }
}
