<?php

namespace App\Actions\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateUser
{
    /**
     * @param  array<string, mixed>  $input
     */
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

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, self::rules())->validate();
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => [
                'required',
                Rule::in([UserRole::ADMIN, UserRole::USER]),
            ],
        ];
    }
}
