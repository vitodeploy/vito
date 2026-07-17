<?php

namespace App\Actions\Desktop;

use App\Actions\User\CreateUser;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SetupDesktopAdmin
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'password' => ['required', 'string', 'confirmed'],
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = app(CreateUser::class)->create([
                'name' => $input['name'] ?? null,
                'email' => $input['email'] ?? null,
                'password' => $input['password'] ?? null,
                'role' => UserRole::ADMIN->value,
            ]);

            $user->ensureHasDefaultProject();

            return $user;
        });
    }
}
