<?php

namespace App\Actions\ServerProvider;

use App\Models\ServerProvider;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EditServerProvider
{
    public function edit(ServerProvider $serverProvider, User $user, array $input): void
    {
        $this->validate($input);

        $serverProvider->profile = $input['name'];
        $serverProvider->project_id = isset($input['global']) && $input['global'] ? null : $user->current_project_id;

        $serverProvider->save();
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        $rules = [
            'name' => [
                'required',
            ],
        ];
        Validator::make($input, $rules)->validate();
    }
}
