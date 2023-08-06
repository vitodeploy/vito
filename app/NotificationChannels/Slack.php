<?php

namespace App\NotificationChannels;

use Illuminate\Support\Facades\Http;

class Slack extends AbstractProvider
{
    public function validationRules(): array
    {
        return [
            'webhook_url' => 'required|url',
        ];
    }

    public function data(array $input): array
    {
        return [
            'webhook_url' => $input['webhook_url'],
        ];
    }

    public function connect(): bool
    {
        $connect = $this->checkConnection(
            __('Congratulations! 🎉'),
            __("You've connected your Slack to Vito")."\n".
            __('Manage your notification channels')."\n".
            route('notification-channels')
        );

        if (! $connect) {
            return false;
        }

        return true;
    }

    public function sendMessage(string $subject, string $text): void
    {
        dispatch(function () use ($subject, $text) {
            $data = $this->notificationChannel->data;
            Http::post($data['webhook_url'], [
                'text' => '*'.$subject.'*'."\n".$text,
            ]);
        });
    }

    private function checkConnection(string $subject, string $text): bool
    {
        $data = $this->notificationChannel->data;
        $connect = Http::post($data['webhook_url'], [
            'text' => '*'.$subject.'*'."\n".$text,
        ]);

        return $connect->ok();
    }
}
