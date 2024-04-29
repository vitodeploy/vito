<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateProject
{
    public function update(Project $project, array $input): Project
    {
        if (isset($input['name'])) {
            $input['name'] = strtolower($input['name']);
        }

        $this->validate($project, $input);

        $project->name = $input['name'];

        $project->save();

        return $project;
    }

    private function validate(Project $project, array $input): void
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects')->where('user_id', $project->user_id)->ignore($project->id),
            ],
        ])->validate();
    }
}
