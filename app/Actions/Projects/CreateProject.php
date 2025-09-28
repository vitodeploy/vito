<?php

namespace App\Actions\Projects;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateProject
{
    public function create(User $user, array $input): Project
    {
        $this->validate($input);

        $project = new Project([
            'name' => $input['name'],
        ]);
        $project->save();

        $project->users()->create([
            'user_id' => $user->id,
            'role' => UserRole::OWNER,
        ]);

        $user->current_project_id = $project->id;
        $user->save();

        return $project;
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
                'lowercase:projects,name',
            ],
        ])->validate();
    }
}
