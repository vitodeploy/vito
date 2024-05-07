<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateProject
{
    public function create(User $user, array $input): Project
    {
        if (isset($input['name'])) {
            $input['name'] = strtolower($input['name']);
        }

        $this->validate($input);

        $project = new Project([
            'name' => $input['name'],
        ]);

        $project->save();

        $project->users()->attach($user);

        return $project;
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:projects,name',
            ],
        ])->validate();
    }
}
