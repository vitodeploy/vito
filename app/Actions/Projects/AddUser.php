<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AddUser
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function add(Project $project, array $input): void
    {
        Validator::make($input, self::rules($project))->validate();

        /** @var User $user */
        $user = User::query()->findOrFail($input['user']);

        $project->users()->detach($user);
        $project->users()->attach($user);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(Project $project): array
    {
        return [
            'user' => [
                'required',
                Rule::exists('users', 'id'),
                Rule::unique('user_project', 'user_id')->where(function (Builder $query) use ($project): void {
                    $query->where('project_id', $project->id);
                }),
            ],
        ];
    }
}
