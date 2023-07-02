<?php

namespace App\NotificationChannels;

use App\Mail\NotificationChannelMessage;
use Illuminate\Support\Facades\Mail;

class Email extends AbstractProvider
{
    public function validationRules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }

    public function data(array $input): array
    {
        return [
            'email' => $input['email'],
        ];
    }

    public function connect(): bool
    {
        $this->notificationChannel->connected = true;
        $this->notificationChannel->save();

        return true;
    }

    public function sendMessage(string $subject, mixed $text): void
    {
        $data = $this->notificationChannel->data;
        Mail::to($data['email'])->send(new NotificationChannelMessage($subject, $text));
    }
}
