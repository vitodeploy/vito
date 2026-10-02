<?php

namespace App\WorkflowActions\General;

use App\Models\NotificationChannel;
use App\Notifications\GenericNotification;
use App\WorkflowActions\AbstractWorkflowAction;
use Illuminate\Support\Facades\Validator;

class Notify extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'notification_channel_id' => 'The ID of the notification channel to send the notification to',
            'email' => 'The email address of the user on Vito',
            'message' => 'The message to send',
        ];
    }

    public function outputs(): array
    {
        return [];
    }

    public function run(array $input): array
    {
        Validator::make($input, [
            'notification_channel_id' => ['required', 'integer', 'exists:notification_channels,id'],
            'message' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
        ])->validate();

        $notificationChannel = NotificationChannel::query()->findOrFail($input['notification_channel_id']);

        $this->authorize('update', $notificationChannel);

        $notificationChannel->provider()->send($notificationChannel, new GenericNotification($input['message']));

        return [];
    }
}
