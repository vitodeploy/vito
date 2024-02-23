<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class FailedToDeleteServerFromProvider extends AbstractNotification
{
    use Queueable;

    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function rawText(): string
    {
        return __("We couldn't delete [:server] \nfrom :provider \nPlease check your provider and delete it manually", [
            'server' => $this->server->name,
            'provider' => $this->server->provider,
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Failed to delete the server from the provider!'))
            ->line("We couldn't delete [".$this->server->name.'] from '.$this->server->provider)
            ->line('Please check your provider and delete it manually');
    }
}
