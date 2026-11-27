<?php

namespace App\WorkflowActions\General;

use App\DTOs\DynamicField;
use App\Models\NotificationChannel;
use App\Notifications\GenericNotification;
use App\WorkflowActions\AbstractWorkflowAction;
use Illuminate\Support\Facades\Validator;

class Notify extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            DynamicField::make('notification_channel_id')->resource('notification_channel')->label('Notification Channel'),
            DynamicField::make('message')->textarea()->label('Message'),
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
        ])->validate();

        $notificationChannel = NotificationChannel::query()->findOrFail($input['notification_channel_id']);

        $this->authorize('update', $notificationChannel);

        $notificationChannel->provider()->send($notificationChannel, new GenericNotification($input['message']));

        return [];
    }
}
