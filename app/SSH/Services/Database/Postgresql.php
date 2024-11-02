<?php

namespace App\SSH\Services\Database;

use App\Exceptions\SSHError;

class Postgresql extends AbstractDatabase
{
    protected function getScriptsDir(): string
    {
        return 'postgresql';
    }

    /**
     * @throws SSHError
     */
    public function create(string $name): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript($this->getScriptsDir().'/create.sh', [
                'name' => $name,
                'ssh_user' => $this->service->server->ssh_user,
            ]),
            'create-database'
        );
    }
}
