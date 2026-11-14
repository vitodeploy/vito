<?php

namespace App\Actions\PHP;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\Service;
use App\Services\PHP\PHP;
use RuntimeException;

class EnsureFpmPool
{
    /**
     * @throws SSHError
     */
    public function ensure(Server $server, string $user, string $phpVersion): void
    {
        $service = $server->services()->where('type', 'php')->where('version', $phpVersion)->first();
        if (! $service instanceof Service) {
            throw new RuntimeException('PHP service not found');
        }

        $server->isolatedUserLock($user)->block(30, function () use ($server, $user, $phpVersion, $service): void {
            try {
                $server->ssh()->exec(view('ssh.site.check-fpm-pool-exists', [
                    'user' => $user,
                    'version' => $phpVersion,
                ]));
            } catch (SSHCommandError) {
                /** @var PHP $php */
                $php = $service->handler();
                $php->createFpmPool($user, $phpVersion);
            }

            $check = view('ssh.site.check-fpm-pool-ready', ['user' => $user, 'version' => $phpVersion]);
            try {
                $server->ssh()->exec($check);
            } catch (SSHCommandError) {
                $server->systemd()->restart($service->handler()->unit());
                $server->ssh()->exec($check);
            }
        });
    }
}
