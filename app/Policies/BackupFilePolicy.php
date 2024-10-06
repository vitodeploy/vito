<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BackupFilePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Backup $backup): bool
    {
        return ($user->isAdmin() || $backup->server->project->users->contains($user)) && $backup->server->isReady();
    }

    public function view(User $user, BackupFile $backupFile): bool
    {
        return ($user->isAdmin() || $backupFile->backup->server->project->users->contains($user)) &&
            $backupFile->backup->server->isReady();
    }

    public function create(User $user, Backup $backup): bool
    {
        return ($user->isAdmin() || $backup->server->project->users->contains($user)) && $backup->server->isReady();
    }

    public function update(User $user, BackupFile $backupFile): bool
    {
        return ($user->isAdmin() || $backupFile->backup->server->project->users->contains($user)) &&
            $backupFile->backup->server->isReady();
    }

    public function delete(User $user, BackupFile $backupFile): bool
    {
        return ($user->isAdmin() || $backupFile->backup->server->project->users->contains($user)) &&
            $backupFile->backup->server->isReady();
    }
}
