<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerInstallationStarted extends AbstractNotification
{
    public function __construct(protected Server $server) {}

    public function rawText(): string
    {
        return __("Installation started for server [:server]\nThis may take several minutes depending on many things like your server's internet speed.\nAs soon as it finishes, We will notify you through this channel.\nYou can check the progress live on your dashboard.\n:progress", [
            'server' => $this->server->name,
            'progress' => url('/servers/'.$this->server->id),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Server installation started'))
            ->line(__('We\'ve started installing your server [:server].', ['server' => $this->server->name]))
            ->line(__('This may take several minutes depending on factors like your server\'s network speed.'))
            ->line(__('We\'ll notify you through this channel as soon as it\'s ready. You can also follow the progress live on your dashboard.'))
            ->action(__('View progress'), url('/servers/'.$this->server->id));
    }
}
