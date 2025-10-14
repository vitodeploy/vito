<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class GenericNotification extends AbstractNotification
{
    public function __construct(public string $message) {}

    public function rawText(): string
    {
        return $this->message;
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Notification'))
            ->line($this->message);
    }
}
