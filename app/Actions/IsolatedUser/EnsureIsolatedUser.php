<?php

namespace App\Actions\IsolatedUser;

use App\Exceptions\SSHError;
use App\Models\Server;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Str;
use RuntimeException;

class EnsureIsolatedUser
{
    /**
     * @throws SSHError
     */
    public function ensure(Server $server, string $username, ?int $siteId = null): void
    {
        try {
            $server->isolatedUserLock($username)->block(30, function () use ($server, $username, $siteId): void {
                $server->os()->createIsolatedUser($username, Str::random(15), $siteId);
            });
        } catch (LockTimeoutException) {
            throw new RuntimeException("Could not acquire isolated-user lock for '{$username}' on server {$server->id} within 30s.");
        }
    }
}
