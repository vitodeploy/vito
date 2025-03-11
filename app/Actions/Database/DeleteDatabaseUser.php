<?php

namespace App\Actions\Database;

use App\Models\DatabaseUser;
use App\Models\Server;

class DeleteDatabaseUser
{
    public function delete(Server $server, DatabaseUser $databaseUser): void
    {
        $service = $server->database();
        if (! $service instanceof \App\Models\Service) {
            throw new \Exception('Database service not found');
        }
        /** @var \App\SSH\Services\Database\Database $handler */
        $handler = $service->handler();
        $handler->deleteUser($databaseUser->username, $databaseUser->host);
        $databaseUser->delete();
    }
}
