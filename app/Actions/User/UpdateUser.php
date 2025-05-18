<?php

namespace App\Actions\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateUser
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): User
    {
        $this->validate($user, $input);

        $user->name = $input['name'];
        $user->email = $input['email'];
        $user->role = $input['role'];

        if (isset($input['password'])) {
            $user->password = bcrypt($input['password']);
        }

        $user->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(User $user, array $input): void
    {
        Validator::make($input, self::rules($user))->validate();
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => [
                'required',
                Rule::in([UserRole::ADMIN, UserRole::USER]),
            ],
        ];
    }
}
