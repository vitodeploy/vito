<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateUserProfileInformation
{
    public function update(User $user, array $input): void
    {
        if ($input['email'] !== $user->email) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'timezone' => $input['timezone'],
            ])->save();
        }
    }

    public static function rules(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'timezone' => [
                'required',
                Rule::in(timezone_identifiers_list()),
            ],
        ];
    }

    /**
     * Update the given verified user's profile information.
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'timezone' => $input['timezone'],
        ])->save();
    }
}
