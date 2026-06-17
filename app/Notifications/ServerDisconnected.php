<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerDisconnected extends AbstractNotification
{
    public function __construct(protected Server $server) {}

    public function rawText(): string
    {
        return __('Connection lost to the server [:server]', [
            'server' => $this->server->name,
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Server connection lost'))
            ->line(__('Vito has lost connection to your server [:server].', ['server' => $this->server->name]))
            ->line(__('Please make sure the server is online and still has Vito\'s public keys installed.'))
            ->action(__('Check server'), url('/servers/'.$this->server->id));
    }
}
