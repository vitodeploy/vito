<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateProject
{
    /**
     * @param  array<string, mixed>  $input
     */
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

    /**
     * @return array<string, array<string>>
     */
    public static function rules(Project $project): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')->ignore($project->id),
                'lowercase:projects,name',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Project $project, array $input): void
    {
        Validator::make($input, self::rules($project))->validate();
    }
}
