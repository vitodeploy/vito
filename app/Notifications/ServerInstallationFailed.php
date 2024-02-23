<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerInstallationFailed extends AbstractNotification
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

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
            ->subject(__('Server installation failed!'))
            ->line('Your server ['.$this->server->name.'] installation has been failed.')
            ->line('Check your server logs')
            ->action('View Logs', url('/servers/'.$this->server->id.'/logs'));
    }
}
