<?php

namespace App\Notifications;

use App\Contracts\Notification;
use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerInstallationSucceed implements Notification
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function subject(): string
    {
        return __('Server installation succeed!');
    }

    public function message(bool $mail = false): mixed
    {
        $this->server->refresh();

        if ($mail) {
            return $this->mail();
        }

        return __("Installation succeed for server [:server] \nServer IP: :ip \nUser: :user\nPassword: :password\n:link", [
            'server' => $this->server->name,
            'ip' => $this->server->ip,
            'user' => $this->server->authentication['user'],
            'password' => $this->server->authentication['pass'],
            'link' => url('/servers/'.$this->server->id),
        ]);
    }

    public function mail(): MailMessage
    {
        $this->server->refresh();

        return (new MailMessage)
            ->line('Your server ['.$this->server->name.'] has been installed successfully.')
            ->line('Server IP: '.$this->server->ip)
            ->line('User: '.$this->server->authentication['user'])
            ->line('Password: '.$this->server->authentication['pass'])
            ->action('Manage Your Server', url('/login'));
    }
}
