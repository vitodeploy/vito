<?php

namespace App\Actions\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateUser
{
    public function create(array $input): User
    {
        $this->validate($input);

        /** @var User $user */
        $user = User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'role' => $input['role'],
            'password' => bcrypt($input['password']),
            'timezone' => 'UTC',
        ]);

        return $user;
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => [
                'required',
                Rule::in([UserRole::ADMIN, UserRole::USER]),
            ],
        ])->validate();
    }
}
