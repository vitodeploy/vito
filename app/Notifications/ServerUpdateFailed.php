<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerUpdateFailed extends AbstractNotification
{
    public function __construct(protected Server $server) {}

    public function rawText(): string
    {
        return __("Update failed for server [:server] \nCheck your server's logs \n:logs", [
            'server' => $this->server->name,
            'logs' => url('/servers/'.$this->server->id.'/logs'),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Server update failed!'))
            ->line('Your server ['.$this->server->name.'] update has been failed.')
            ->line('Check your server logs')
            ->action('View Logs', url('/servers/'.$this->server->id.'/logs'));
    }
}
