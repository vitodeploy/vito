<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Notifications\Messages\MailMessage;

class SiteInstallationFailed extends AbstractNotification
{
    public function __construct(protected Site $site) {}

    public function rawText(): string
    {
        return __("Installation failed for site [:site]\nOnce you've corrected the errors, you can retry the installation from the site's page.\n:link", [
            'site' => $this->site->domain,
            'link' => url('/servers/'.$this->site->server_id.'/sites/'.$this->site->id),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Site installation failed'))
            ->line(__('Unfortunately, the installation of your site [:domain] failed.', ['domain' => $this->site->domain]))
            ->line(__('Once you\'ve corrected the errors, you can retry the installation from the site\'s page.'))
            ->action(__('View site'), url('/servers/'.$this->site->server_id.'/sites/'.$this->site->id));
    }
}
