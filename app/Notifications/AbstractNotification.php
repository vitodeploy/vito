<?php

namespace App\Notifications;

use App\Contracts\Notification as NotificationInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

abstract class AbstractNotification extends Notification implements NotificationInterface, ShouldQueue
{
    use Queueable, SerializesModels;

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
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
