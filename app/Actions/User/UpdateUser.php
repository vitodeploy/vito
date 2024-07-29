<?php

namespace App\Actions\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateUser
{
    public function update(User $user, array $input): void
    {
        $this->validate($user, $input);

        $user->name = $input['name'];
        $user->email = $input['email'];
        $user->timezone = $input['timezone'];
        $user->role = $input['role'];

        if (isset($input['password']) && $input['password'] !== null) {
            $user->password = bcrypt($input['password']);
        }

        $user->save();
    }

    private function validate(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'timezone' => [
                'required',
                Rule::in(timezone_identifiers_list()),
            ],
            'role' => [
                'required',
                Rule::in([UserRole::ADMIN, UserRole::USER]),
                function ($attribute, $value, $fail) use ($user) {
                    if ($user->is(auth()->user()) && $value !== $user->role) {
                        $fail('You cannot change your own role');
                    }
                },
            ],
        ])->validate();
    }
}
