<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use App\Models\UserProject;
use Illuminate\Validation\ValidationException;

class AcceptProjectInvitation
{
    public function find(Project $project, User $user, string $token): ?UserProject
    {
        /** @var ?UserProject $invitation */
        $invitation = $project->users()
            ->whereNull('user_id')
            ->where('email', $user->email)
            ->where('invitation_token', InviteToProject::hashToken($token))
            ->first();

        return $invitation;
    }

    /**
     * @throws ValidationException
     */
    public function accept(Project $project, User $user, string $token): void
    {
        $invitation = $this->findOrFail($project, $user, $token);

        if ($project->users()->where('user_id', $user->id)->exists()) {
            $invitation->delete();

            return;
        }

        $invitation->email = null;
        $invitation->user_id = $user->id;
        $invitation->invitation_token = null;
        $invitation->save();
    }

    /**
     * @throws ValidationException
     */
    public function decline(Project $project, User $user, string $token): void
    {
        $this->findOrFail($project, $user, $token)->delete();
    }

    /**
     * @throws ValidationException
     */
    private function findOrFail(Project $project, User $user, string $token): UserProject
    {
        return $this->find($project, $user, $token) ?? throw ValidationException::withMessages([
            'invitation' => __('This invitation is no longer valid.'),
        ]);
    }
}
