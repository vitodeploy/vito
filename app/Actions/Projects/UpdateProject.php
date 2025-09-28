<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Validator;

class UpdateProject
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Project $project, array $input): Project
    {
        if (isset($input['name'])) {
            $input['name'] = strtolower((string) $input['name']);
        }

        $this->validate($input);

        $project->name = $input['name'];

        $project->save();

        return $project;
    }

    private function validate(array $input): void
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                'lowercase:projects,name',
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
