<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateProject
{
    public function create(User $user, array $input): Project
    {
        $this->validate($user, $input);

        $project = new Project([
            'name' => $input['name'],
        ]);

        $project->save();

        $project->users()->attach($user->id);

        return $project;
    }

    private function validate(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:projects,name,NULL,id,user_id,'.$user->id,
            ],
        ])->validate();
    }
}
