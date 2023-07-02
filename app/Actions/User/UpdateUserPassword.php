<?php

namespace App\Actions\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UpdateUserPassword
{
    /**
     * Validate and update the user's password.
     */
    public function update($user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current-password'],
            'password' => ['required', 'string'],
            'password_confirmation' => ['required', 'same:password'],
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
