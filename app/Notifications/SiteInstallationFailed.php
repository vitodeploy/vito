<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Notifications\Messages\MailMessage;

class SiteInstallationFailed extends AbstractNotification
{
    public function __construct(protected Site $site) {}

    public function rawText(): string
    {
        return __("Installation failed for site [:site] \nCheck your server's logs \n:logs", [
            'site' => $this->site->domain,
            'logs' => url('/servers/'.$this->site->server_id.'/logs'),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Site installation failed!'))
            ->line('Your site\'s ['.$this->site->domain.'] installation has been failed.')
            ->line('Check your server logs')
            ->action('View Logs', url('/servers/'.$this->site->server_id.'/logs'));
    }
}
