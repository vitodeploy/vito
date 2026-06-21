<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Notifications\Messages\MailMessage;

class SiteInstallationSucceed extends AbstractNotification
{
    public function __construct(protected Site $site) {}

    public function rawText(): string
    {
        return __('Installation succeed for site [:site]', [
            'site' => $this->site->domain,
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->success()
            ->subject(__('Your site is ready'))
            ->line(__('Your site [:domain] has been installed successfully.', ['domain' => $this->site->domain]))
            ->action(__('Open site'), url('/servers/'.$this->site->server_id.'/sites/'.$this->site->id));
    }
}
