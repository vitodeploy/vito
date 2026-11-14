<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use App\Models\UserProject;

class AcceptProjectInvitation
{
    public function find(Project $project, User $user, string $token): UserProject
    {
        /** @var UserProject $invitation */
        $invitation = $project->users()
            ->whereNull('user_id')
            ->where('email', $user->email)
            ->where('invitation_token', InviteToProject::hashToken($token))
            ->firstOrFail();

        return $invitation;
    }

    public function accept(Project $project, User $user, string $token): void
    {
        $invitation = $this->find($project, $user, $token);

        $invitation->email = null;
        $invitation->user_id = $user->id;
        $invitation->invitation_token = null;
        $invitation->save();
    }
}
