<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerInstallationSucceed extends AbstractNotification
{
    public function __construct(protected Server $server) {}

    public function rawText(): string
    {
        $this->server->refresh();

        return __("Installation succeed for server [:server] \nServer IP: :ip \nUser: :user\nPassword: :password\n:link", [
            'server' => $this->server->name,
            'ip' => $this->server->ip,
            'user' => $this->server->authentication['user'],
            'password' => $this->server->authentication['pass'],
            'link' => url('/servers/'.$this->server->id),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        $this->server->refresh();

        return (new MailMessage)
            ->success()
            ->subject(__('Your server is ready'))
            ->line(__('Your server [:server] has been installed successfully and is ready to use.', ['server' => $this->server->name]))
            ->line(__('Server IP: :ip', ['ip' => $this->server->ip]))
            ->line(__('SSH user: :user', ['user' => $this->server->authentication['user']]))
            ->line(__('SSH password: :password', ['password' => $this->server->authentication['pass']]))
            ->action(__('Manage your server'), url('/servers/'.$this->server->id));
    }
}
