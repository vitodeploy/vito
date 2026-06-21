<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerInstallationFailed extends AbstractNotification
{
    public function __construct(protected Server $server) {}

    public function rawText(): string
    {
        return __("Installation failed for server [:server] \nCheck your server's logs \n:logs", [
            'server' => $this->server->name,
            'logs' => url('/servers/'.$this->server->id.'/logs'),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Server installation failed'))
            ->line(__('Unfortunately, the installation of your server [:server] failed.', ['server' => $this->server->name]))
            ->line(__('Check the server logs to find out what went wrong.'))
            ->action(__('View logs'), url('/servers/'.$this->server->id.'/logs'));
    }
}
