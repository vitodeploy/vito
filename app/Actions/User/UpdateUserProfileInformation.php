<?php

namespace App\Actions\User;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @throws Exception
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'timezone' => [
                'required',
                Rule::in(timezone_identifiers_list()),
            ],
        ])->validateWithBag('updateProfileInformation');

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
