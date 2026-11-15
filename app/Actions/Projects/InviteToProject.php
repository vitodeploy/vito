<?php

namespace App\Actions\Projects;

use App\Enums\UserRole;
use App\Mail\ProjectInvitation;
use App\Models\Project;
use App\Models\UserProject;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class InviteToProject
{
    public function invite(Project $project, array $input): void
    {
        $this->validate($project, $input);

        $token = Str::random(64);

        /** @var UserProject $invitation */
        $invitation = $project->users()->make([
            'email' => $input['email'],
            'role' => UserRole::from($input['role']),
        ]);
        $invitation->invitation_token = self::hashToken($token);
        $invitation->save();

        try {
            Mail::to($input['email'])->send(new ProjectInvitation($project, $token));
        } catch (Throwable) {
            $invitation->delete();

            throw ValidationException::withMessages([
                'email' => __("Couldn't send the invitation email. Check the mail settings and try again."),
            ]);
        }
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
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
