<?php

namespace App\Actions\NotificationChannels;

use App\Models\NotificationChannel;
use App\Models\User;

class EditChannel
{
    public function edit(NotificationChannel $notificationChannel, User $user, array $input): void
    {
        $notificationChannel->fill([
            'label' => $input['label'],
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);
        $notificationChannel->save();
    }

    public static function rules(array $input): array
    {
        return [
            'label' => 'required',
        ];
    }
}
