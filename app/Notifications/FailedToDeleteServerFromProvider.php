<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class FailedToDeleteServerFromProvider extends AbstractNotification
{
    public function __construct(protected Server $server) {}

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
            ->error()
            ->subject(__('Failed to delete server from provider'))
            ->line(__('We couldn\'t delete [:server] from :provider.', [
                'server' => $this->server->name,
                'provider' => $this->server->provider,
            ]))
            ->line(__('Please check your provider and remove it manually.'));
    }
}
