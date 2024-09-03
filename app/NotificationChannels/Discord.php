<?php

namespace App\NotificationChannels;

use App\Models\NotificationChannel;
use App\Notifications\NotificationInterface;
use Illuminate\Support\Facades\Http;

class Discord extends AbstractNotificationChannel
{
    public function createRules(array $input): array
    {
        return [
            'webhook_url' => 'required|url',
        ];
    }

    public function createData(array $input): array
    {
        return [
            'webhook_url' => $input['webhook_url'] ?? '',
        ];
    }

    public function data(): array
    {
        return [
            'webhook_url' => $this->notificationChannel->data['webhook_url'] ?? '',
        ];
    }

    public function connect(): bool
    {
        $connect = $this->checkConnection(
            __('Congratulations! 🎉'),
            __("You've connected your Discord to :app", ['app' => config('app.name')])."\n".
            __('Manage your notification channels')."\n".
            route('settings.notification-channels')
        );

        if (! $connect) {
            $this->notificationChannel->delete();

            return false;
        }

        $this->notificationChannel->connected = true;
        $this->notificationChannel->save();

        return true;
    }

    private function checkConnection(string $subject, string $text): bool
    {
        $connect = Http::post($this->data()['webhook_url'], [
            'content' => '*'.$subject.'*'."\n".$text,
        ]);

        return $connect->successful();
    }

    public function send(object $notifiable, NotificationInterface $notification): void
    {
        /** @var NotificationChannel $notifiable */
        $this->notificationChannel = $notifiable;
        $data = $this->notificationChannel->data;
        Http::post($data['webhook_url'], [
            'content' => $notification->toSlack($notifiable),
        ]);
    }
}
