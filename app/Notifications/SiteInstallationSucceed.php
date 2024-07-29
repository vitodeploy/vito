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
            ->subject(__('Site installation succeed!'))
            ->line('Your site\'s ['.$this->site->domain.'] installation has been installed.')
            ->line('Check your site')
            ->action('View Site', url('/servers/'.$this->site->server_id.'/sites/'.$this->site->id));
    }
}
