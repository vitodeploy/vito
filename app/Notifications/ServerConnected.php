<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerConnected extends AbstractNotification
{
    public function __construct(protected Server $server) {}

    public function rawText(): string
    {
        return __('Connection successful to the server [:server]', [
            'server' => $this->server->name,
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->success()
            ->subject(__('Server reconnected'))
            ->line(__('Vito has successfully connected to your server [:server].', ['server' => $this->server->name]))
            ->action(__('View server'), url('/servers/'.$this->server->id));
    }
}
