<?php

namespace App\Notifications;

use App\Contracts\Notification;
use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerInstallationStarted implements Notification
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function subject(): string
    {
        return __('Server installation started!');
    }

    public function message(bool $mail = false): mixed
    {
        if ($mail) {
            return $this->mail();
        }

        return __("Installation started for server [:server]\nThis may take several minutes depending on many things like your server's internet speed.\nAs soon as it finishes, We will notify you through this channel.\nYou can check the progress live on your dashboard.\n:progress", [
            'server' => $this->server->name,
            'progress' => url('/servers/'.$this->server->id),
        ]);
    }

    public function mail(): MailMessage
    {
        return (new MailMessage)
            ->line('Your server\'s ['.$this->server->name.'] installation has been started.')
            ->line("This may take several minutes depending on many things like your server's internet speed.")
            ->line('As soon as it finishes, We will notify you through this channel.')
            ->line('You can check the progress live on your dashboard.')
            ->action('Installation Progress', url('/servers/'.$this->server->id));
    }
}
