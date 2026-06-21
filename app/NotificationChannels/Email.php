<?php

namespace App\NotificationChannels;

use App\Models\NotificationChannel;
use App\NotificationChannels\Email\NotificationMail;
use App\Notifications\NotificationInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Throwable;

class Email extends AbstractNotificationChannel
{
    public static function id(): string
    {
        return 'email';
    }

    public function createRules(array $input): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],
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
            $message = (new MailMessage)
                ->success()
                ->subject(__('Email notifications connected'))
                ->line(__('This confirms that email notifications are now connected for your Vito instance.'));
            Mail::to($this->data()['email'])->send(
                new NotificationMail($message->subject, $message->render())
            );
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    public function send(object $notifiable, NotificationInterface $notification): void
    {
        /** @var NotificationChannel $notifiable */
        $this->notificationChannel = $notifiable;
        $message = $notification->toEmail($notifiable);
        Mail::to($this->data()['email'])->send(
            new NotificationMail($message->subject, $message->render())
        );
    }
}
