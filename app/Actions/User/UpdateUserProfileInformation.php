<?php

namespace App\Actions\User;

use App\Models\User;
use App\ValidationRules\TimezoneRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Fortify\Features;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'timezone' => ['nullable', 'string', 'max:255', new TimezoneRule],
        ])->validateWithBag('updateProfileInformation');

        if ($input['email'] !== $user->email && Features::enabled(Features::emailVerification())) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'timezone' => $input['timezone'] ?? 'UTC',
            ])->save();
        }
    }

    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'timezone' => $input['timezone'] ?? 'UTC',
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
