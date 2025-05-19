<?php

namespace App\Actions\NotificationChannels;

use App\Models\NotificationChannel;
use App\Models\User;

class EditChannel
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(NotificationChannel $notificationChannel, User $user, array $input): void
    {
        $notificationChannel->fill([
            'label' => $input['name'],
            'project_id' => isset($input['global']) && $input['global'] ? null : $user->current_project_id,
        ]);
        $notificationChannel->save();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function rules(array $input): array
    {
        return [
            'name' => 'required',
        ];
    }
}
