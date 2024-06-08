<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerDisconnected extends AbstractNotification
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function rawText(): string
    {
        return __("We've disconnected from your server [:server]", [
            'server' => $this->server->name,
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Server disconnected!'))
            ->line("We've disconnected from your server [".$this->server->name.'].')
            ->line('Please check your server is online and make sure that has our public keys in it');
    }
}
