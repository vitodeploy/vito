<?php

namespace App\Notifications;

use App\Contracts\Notification;
use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerInstallationFailed implements Notification
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function subject(): string
    {
        return __('Server installation failed!');
    }

    public function message(bool $mail = false): mixed
    {
        if ($mail) {
            return $this->mail();
        }

        return __("Installation failed for server [:server] \nCheck your server's logs \n:logs", [
            'server' => $this->server->name,
            'logs' => url('/servers/'.$this->server->id.'/logs'),
        ]);
    }

    private function mail(): MailMessage
    {
        return (new MailMessage)
            ->line('Your server ['.$this->server->name.'] installation has been failed.')
            ->line('Check your server logs')
            ->action('View Logs', url('/servers/'.$this->server->id.'/logs'));
    }
}
