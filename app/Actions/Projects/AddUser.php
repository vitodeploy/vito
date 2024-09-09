<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class AddUser
{
    public function add(Project $project, array $input): void
    {
        //
    }

    public static function rules(Project $project): array
    {
        return [
            'user' => [
                'required',
                Rule::exists('users', 'id'),
                Rule::unique('user_project', 'user_id')->where(function (Builder $query) use ($project) {
                    $query->where('project_id', $project->id);
                }),
            ],
        ];
    }
}
