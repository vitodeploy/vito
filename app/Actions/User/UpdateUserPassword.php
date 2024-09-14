<?php

namespace App\Actions\User;

use Illuminate\Support\Facades\Hash;

class UpdateUserPassword
{
    public function update($user, array $input): void
    {
        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }

    public static function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current-password'],
            'password' => ['required', 'string'],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }
}
