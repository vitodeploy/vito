<?php

namespace App\Contracts;

use Illuminate\Notifications\Messages\MailMessage;

interface Notification
{
    public function rawText(): string;

    public function toMail(object $notifiable): MailMessage;

    public function toSlack(object $notifiable): string;

    public function toDiscord(object $notifiable): string;

    public function toTelegram(object $notifiable): string;
}
