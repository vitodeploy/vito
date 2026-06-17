<?php

namespace App\Notifications;

use App\Models\Ssl;
use Illuminate\Notifications\Messages\MailMessage;

class SslCertificateExpiring extends AbstractNotification
{
    public function __construct(protected Ssl $ssl) {}

    public function rawText(): string
    {
        return __("The SSL certificate for [:domain] :expiry.\nRenew it to keep the site served over HTTPS.\n:link", [
            'domain' => $this->domainLabel(),
            'expiry' => $this->expiryLabel(),
            'link' => $this->link(),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('SSL certificate expiring soon'))
            ->line(__('The SSL certificate for [:domain] :expiry.', [
                'domain' => $this->domainLabel(),
                'expiry' => $this->expiryLabel(),
            ]))
            ->line(__('Renew the certificate to keep the site served over HTTPS without interruption.'))
            ->action(__('Manage certificates'), $this->link());
    }

    private function domainLabel(): string
    {
        $domains = array_filter((array) $this->ssl->domains);

        if (! empty($domains)) {
            return implode(', ', $domains);
        }

        return (string) $this->ssl->site?->domain;
    }

    private function expiryLabel(): string
    {
        $expiresAt = $this->ssl->expires_at;

        if ($expiresAt === null) {
            return __('is expiring soon');
        }

        if ($expiresAt->isPast()) {
            return __('has expired');
        }

        return __('expires in :days day(s) (:date)', [
            'days' => (int) ceil(now()->diffInDays($expiresAt, false)),
            'date' => $expiresAt->toFormattedDayDateString(),
        ]);
    }

    private function link(): string
    {
        return url('/servers/'.$this->ssl->site?->server_id.'/sites/'.$this->ssl->site_id.'/domains');
    }
}
