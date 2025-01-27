<?php

namespace App\SSH\Cron;

use App\Exceptions\SSHError;
use App\Models\Server;

class Cron
{
    public function __construct(protected Server $server) {}

    /**
     * @throws SSHError
     */
    public function update(string $user, string $cron): void
    {
        $this->server->ssh()->exec(
            view('ssh.cron.update', [
                'cron' => $cron,
                'user' => $user,
            ]),
            'update-cron'
        );
    }
}
