<?php

namespace App\NotificationChannels;

use App\Contracts\Notification;
use App\Mail\NotificationMail;
use App\Models\NotificationChannel;
use Illuminate\Support\Facades\Mail;
use Throwable;

class Email extends AbstractNotificationChannel
{
    public function createRules(array $input): array
    {
        return [
            'email' => 'required|email',
        ];
    }

    public function createData(array $input): array
    {
        return [
            'email' => $input['email'],
        ];
    }

    public function data(): array
    {
        return [
            'email' => $this->notificationChannel->data['email'] ?? '',
        ];
    }

    public function connect(): bool
    {
        try {
            Mail::to($this->data()['email'])->send(
                new NotificationMail(
                    'Connected to VitoDeploy',
                    'This email confirms that you have connected your email to VitoDeploy.'
                )
            );
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    public function send(object $notifiable, Notification $notification): void
    {
        /** @var NotificationChannel $notifiable */
        $this->notificationChannel = $notifiable;
        $message = $notification->toEmail($notifiable);
        Mail::to($this->data()['email'])->send(
            new NotificationMail($message->subject, $message->render())
        );
    }
}
