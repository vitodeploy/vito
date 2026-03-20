<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Site;
use App\Models\Ssl;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class SslPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Site $site, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady() &&
            $site->isReady();
    }

    public function viewAnyServer(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady();
    }

    public function view(User $user, Ssl $ssl, Site $site, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $ssl->site_id === $site->id;
    }

    public function create(User $user, Site $site, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $site->isReady();
    }

    public function createServer(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady();
    }

    public function downloadCsr(User $user, Ssl $ssl, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady() &&
            $ssl->server_id === $server->id;
    }

    public function deleteCertificate(User $user, Ssl $ssl, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $ssl->server_id === $server->id;
    }

    public function activateServer(User $user, Ssl $ssl, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $ssl->server_id === $server->id;
    }

    public function deactivateCertificate(User $user, Ssl $ssl, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $ssl->server_id === $server->id;
    }

    public function update(User $user, Ssl $ssl, Site $site, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $ssl->site_id === $site->id;
    }

    public function delete(User $user, Ssl $ssl, Site $site, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $ssl->site_id === $site->id;
    }
}
