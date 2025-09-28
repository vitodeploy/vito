<?php

namespace App\Actions\Projects;

use App\Enums\UserRole;
use App\Mail\ProjectInvitation;
use App\Models\Project;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class InviteToProject
{
    public function invite(Project $project, array $input): void
    {
        $this->validate($project, $input);

        $project->users()->create([
            'email' => $input['email'],
            'role' => UserRole::from($input['role']),
        ]);

        try {
            Mail::to($input['email'])->send(new ProjectInvitation($project));
        } catch (Throwable) {
            //
        }
    }

    protected function validate(Project $project, array $input): void
    {
        Validator::make($input, $this->rules($project), [
            'email.unique' => __('This user has already been invited to the project.'),
        ])->validate();
    }

    protected function rules(Project $project): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::unique('user_project')->where(function (Builder $query) use ($project) {
                    $query->where('project_id', $project->id);
                }),
                Rule::notIn([
                    ...$project->registeredUsers()->pluck('users.email'),
                ]),
            ],
            'role' => [
                'required',
                Rule::in([
                    UserRole::ADMIN,
                    UserRole::USER,
                ]),
            ],
        ];
    }
}
