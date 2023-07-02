<?php

namespace App\Notifications;

use App\Contracts\Notification;
use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerDisconnected implements Notification
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function subject(): string
    {
        return __('Server disconnected!');
    }

    public function message(bool $mail = false): mixed
    {
        if ($mail) {
            return $this->mail();
        }

        return __("We've disconnected from your server [:server]", [
            'server' => $this->server->name,
        ]);
    }

    public function mail(): MailMessage
    {
        return (new MailMessage)
            ->line("We've disconnected from your server [".$this->server->name.'].')
            ->line('Please check your sever is online and make sure that has our public keys in it');
    }
}
