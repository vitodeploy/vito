<?php

namespace App\Actions\Projects;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddUser
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function add(Project $project, array $input): void
    {
        $this->validate($input);

        /** @var User $user */
        $user = User::query()->where('email', $input['email'])->firstOrFail();

        if ($project->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'email' => __('This user is already added to the project.'),
            ]);
        }

        $project->users()->create([
            'user_id' => $user->id,
            'role' => UserRole::from($input['role']),
        ]);
    }

    private function validate(array $input): void
    {
        $rules = [
            'email' => [
                'required',
                Rule::exists('users', 'email'),
            ],
            'role' => [
                'required',
                Rule::in(UserRole::cases()),
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
