<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SitePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        // المسؤول يمكنه رؤية جميع المواقع
        if ($user->isAdmin()) {
            return $server->isReady() && $server->webserver();
        }
        
        // المستخدم العادي يمكنه رؤية المواقع المرتبطة بالمشروع أو المواقع المرتبطة به مباشرة
        return ($server->project->users->contains($user) && $user->sites()->whereHas('server', function ($query) use ($server) {
            $query->where('id', $server->id);
        })->exists())
            && $server->isReady()
            && $server->webserver();
    }

    public function view(User $user, Site $site, Server $server): bool
    {
        // المسؤول يمكنه رؤية أي موقع
        if ($user->isAdmin()) {
            return $site->server_id === $server->id
                && $site->server->isReady()
                && $site->server->webserver();
        }
        
        // المستخدم العادي يمكنه رؤية المواقع المرتبطة بالمشروع أو المواقع المرتبطة به مباشرة
        return ($site->server->project->users->contains($user) && $site->users->contains($user))
            && $site->server_id === $server->id
            && $site->server->isReady()
            && $site->server->webserver();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user))
            && $server->isReady()
            && $server->webserver();
    }

    public function update(User $user, Site $site, Server $server): bool
    {
        // المسؤول يمكنه تعديل أي موقع
        if ($user->isAdmin()) {
            return $site->server_id === $server->id
                && $site->server->isReady()
                && $site->server->webserver();
        }
        
        // المستخدم العادي يمكنه تعديل المواقع المرتبطة بالمشروع أو المواقع المرتبطة به مباشرة
        return ($site->server->project->users->contains($user) && $site->users->contains($user))
            && $site->server_id === $server->id
            && $site->server->isReady()
            && $site->server->webserver();
    }

    public function delete(User $user, Site $site, Server $server): bool
    {
        // المسؤول يمكنه حذف أي موقع
        if ($user->isAdmin()) {
            return $site->server_id === $server->id
                && $site->server->isReady()
                && $site->server->webserver();
        }
        
        // المستخدم العادي يمكنه حذف المواقع المرتبطة بالمشروع أو المواقع المرتبطة به مباشرة
        return ($site->server->project->users->contains($user) && $site->users->contains($user))
            && $site->server_id === $server->id
            && $site->server->isReady()
            && $site->server->webserver();
    }

    /**
     * تحديد ما إذا كان المستخدم يمكنه إضافة مستخدمين آخرين للموقع
     */
    public function addUser(User $user, Site $site, Server $server): bool
    {
        // فقط المسؤول يمكنه إضافة مستخدمين آخرين للموقع
        return $user->isAdmin()
            && $site->server_id === $server->id
            && $site->server->isReady()
            && $site->server->webserver();
    }
}
