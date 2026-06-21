<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Notifications\Messages\MailMessage;

class WebhookDeploymentFailed extends AbstractNotification
{
    public function __construct(protected Site $site) {}

    public function rawText(): string
    {
        return __("A push-triggered deployment for site [:site] could not be started.\nCheck the server logs for details.\n:link", [
            'site' => $this->site->domain,
            'link' => url('/servers/'.$this->site->server_id.'/logs'),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Deployment failed to start'))
            ->line(__('A deployment for your site [:site] was triggered by a push but could not be started.', [
                'site' => $this->site->domain,
            ]))
            ->line(__('No deployment was run. Check the server logs to find out what went wrong, then deploy again.'))
            ->action(__('View logs'), url('/servers/'.$this->site->server_id.'/logs'));
    }
}
