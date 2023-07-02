<?php

namespace App\Notifications;

use App\Contracts\Notification;
use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class FailedToDeleteServerFromProvider implements Notification
{
    use Queueable;

    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function subject(): string
    {
        return __('Failed to delete the server from the provider!');
    }

    public function message(bool $mail = false): mixed
    {
        if ($mail) {
            return $this->mail();
        }

        return __("We couldn't delete [:server] \nfrom :provider \nPlease check your provider and delete it manually", [
            'server' => $this->server->name,
            'provider' => $this->server->provider,
        ]);
    }

    public function mail(): MailMessage
    {
        return (new MailMessage)
            ->line("We couldn't delete [".$this->server->name.'] from '.$this->server->provider)
            ->line('Please check your provider and delete it manually');
    }
}
