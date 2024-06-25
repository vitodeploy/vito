<?php

namespace App\Actions\NotificationChannels;

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EditChannel
{
    public function edit(NotificationChannel $notificationChannel, User $user, array $input): void
    {
        $this->validate($input);

        $notificationChannel->label = $input['label'];
        $notificationChannel->project_id = isset($input['global']) && $input['global'] ? null : $user->current_project_id;

        $notificationChannel->save();
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        $rules = [
            'label' => [
                'required',
            ],
        ];
        Validator::make($input, $rules)->validate();
    }
}
