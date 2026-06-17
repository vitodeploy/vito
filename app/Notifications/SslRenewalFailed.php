<?php

namespace App\Notifications;

use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Notifications\Messages\MailMessage;

class SslRenewalFailed extends AbstractNotification
{
    public function __construct(protected Server $server, protected Ssl $ssl) {}

    public function rawText(): string
    {
        return __("Automatic renewal of the wildcard SSL certificate for [:domain] on server [:server] failed.\nCheck the server logs and renew it manually before it expires.\n:link", [
            'domain' => $this->domainLabel(),
            'server' => $this->server->name,
            'link' => url('/servers/'.$this->server->id.'/ssl'),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('SSL certificate renewal failed'))
            ->line(__('The automatic renewal of the wildcard SSL certificate for [:domain] on your server [:server] failed.', [
                'domain' => $this->domainLabel(),
                'server' => $this->server->name,
            ]))
            ->line(__('Renew it manually before the current certificate expires to avoid HTTPS downtime. Check the server logs for details.'))
            ->action(__('Manage certificates'), url('/servers/'.$this->server->id.'/ssl'));
    }

    private function domainLabel(): string
    {
        $domains = array_filter((array) $this->ssl->domains);

        if (! empty($domains)) {
            return implode(', ', $domains);
        }

        return (string) $this->ssl->domain?->domain;
    }
}
