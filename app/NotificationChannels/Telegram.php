<?php

namespace App\NotificationChannels;

use App\Models\NotificationChannel;
use App\Notifications\NotificationInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

class Telegram extends AbstractNotificationChannel
{
    protected string $apiUrl = 'https://api.telegram.org/bot';

    public function createRules(array $input): array
    {
        return [
            'bot_token' => 'required|string',
            'chat_id' => 'required',
        ];
    }

    public function createData(array $input): array
    {
        return [
            'bot_token' => $input['bot_token'],
            'chat_id' => $input['chat_id'],
        ];
    }

    public function data(): array
    {
        return [
            'bot_token' => $this->notificationChannel->data['bot_token'] ?? '',
            'chat_id' => $this->notificationChannel->data['chat_id'] ?? '',
        ];
    }

    public function connect(): bool
    {
        try {
            $this->sendToTelegram(__('Connected!'));
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    public function send(object $notifiable, NotificationInterface $notification): void
    {
        /** @var NotificationChannel $notifiable */
        $this->notificationChannel = $notifiable;
        $this->sendToTelegram($notification->toTelegram($notifiable));
    }

    private function sendToTelegram(string $text): void
    {
        Http::post($this->apiUrl.$this->data()['bot_token'].'/sendMessage', [
            'chat_id' => $this->data()['chat_id'],
            'text' => $text,
            'parse_mode' => 'markdown',
            'disable_web_page_preview' => true,
        ])->throw();
    }
}
