<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use App\Models\UserProject;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class AcceptProjectInvitation
{
    public function find(Project $project, User $user, string $token): ?UserProject
    {
        /** @var ?UserProject $invitation */
        $invitation = $this->pending($project, $user, $token)->first();

        return $invitation;
    }

    /**
     * @throws ValidationException
     */
    public function accept(Project $project, User $user, string $token): void
    {
        $alreadyMember = $project->users()->where('user_id', $user->id)->exists();

        $pending = $this->pending($project, $user, $token);
        $affected = $alreadyMember
            ? $pending->delete()
            : $pending->update(['email' => null, 'user_id' => $user->id, 'invitation_token' => null]);

        $this->ensureConsumed($affected);
    }

    /**
     * @throws ValidationException
     */
    public function decline(Project $project, User $user, string $token): void
    {
        $this->ensureConsumed($this->pending($project, $user, $token)->delete());
    }

    /**
     * @return HasMany<UserProject, Project>
     */
    private function pending(Project $project, User $user, string $token): HasMany
    {
        return $project->users()
            ->whereNull('user_id')
            ->where('email', $user->email)
            ->where('invitation_token', InviteToProject::hashToken($token));
    }

    /**
     * @throws ValidationException
     */
    private function ensureConsumed(int $affected): void
    {
        if ($affected === 0) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation is no longer valid.'),
            ]);
        }
    }
}
