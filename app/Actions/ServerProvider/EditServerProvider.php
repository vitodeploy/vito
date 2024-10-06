<?php

namespace App\Actions\ServerProvider;

use App\Models\ServerProvider;
use App\Models\User;

class EditServerProvider
{
    public function edit(ServerProvider $serverProvider, User $user, array $input): void
    {
        $serverProvider->profile = $input['name'];
        $serverProvider->project_id = isset($input['global']) && $input['global'] ? null : $user->current_project_id;

        $serverProvider->save();
    }

    public static function rules(): array
    {
        return [
            'name' => [
                'required',
            ],
        ];
    }
}
