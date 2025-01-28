<?php

namespace App\Notifications;

use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Notifications\Messages\MailMessage;

class DeploymentCompleted extends AbstractNotification
{
    protected Deployment $deployment;

    protected Site $site;

    public function __construct(Deployment $deployment, Site $site)
    {
        $this->deployment = $deployment;
        $this->site = $site;
    }

    public function rawText(): string
    {
        return __('Deployment for site [:site] has completed with status: :status', [
            'site' => $this->site->domain,
            'status' => $this->deployment->status,
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Deployment Completed'))
            ->line('Deployment for site ['.$this->site->domain.'] has completed with status: '.$this->deployment->status);
    }

    public function toSlack(object $notifiable): string
    {
        return $this->rawText();
    }

    public function toDiscord(object $notifiable): string
    {
        return $this->rawText();
    }

    public function toTelegram(object $notifiable): string
    {
        return $this->rawText();
    }
}
