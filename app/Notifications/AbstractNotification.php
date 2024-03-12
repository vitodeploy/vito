<?php

namespace App\Notifications;

use App\Models\NotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

abstract class AbstractNotification extends Notification implements NotificationInterface, ShouldQueue
{
    use Queueable, SerializesModels;

    public function via(object $notifiable): string
    {
        /** @var NotificationChannel $notifiable */
        return get_class($notifiable->provider());
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Notification')
            ->line($this->rawText());
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
