<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

interface NotificationInterface
{
    public function rawText(): string;

    public function toEmail(object $notifiable): MailMessage;

    public function toSlack(object $notifiable): string;

    public function toDiscord(object $notifiable): string;

    public function toTelegram(object $notifiable): string;
}
