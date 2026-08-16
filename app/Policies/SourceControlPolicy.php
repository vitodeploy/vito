<?php

namespace App\Policies;

use App\Models\SourceControl;
use App\Models\User;
use App\Support\TokenProjectScope;
use Illuminate\Auth\Access\HandlesAuthorization;

class SourceControlPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SourceControl $sourceControl): bool
    {
        return $user->id === $sourceControl->user_id
            && TokenProjectScope::allows($user, $sourceControl->project_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SourceControl $sourceControl): bool
    {
        return $user->id === $sourceControl->user_id
            && TokenProjectScope::allows($user, $sourceControl->project_id, write: true);
    }

    public function delete(User $user, SourceControl $sourceControl): bool
    {
        if ($sourceControl->isGithubApp()) {
            return false;
        }

        return $user->id === $sourceControl->user_id
            && TokenProjectScope::allows($user, $sourceControl->project_id, write: true);
    }
}
