<?php

namespace App\Actions\Database;

use App\Models\DatabaseUser;
use App\Models\Server;

class DeleteDatabaseUser
{
    public function delete(Server $server, DatabaseUser $databaseUser): void
    {
        /** @var \App\SSH\Services\Database\Database $handler */
        $handler = $server->database()->handler();
        $handler->deleteUser($databaseUser->username, $databaseUser->host);
        $databaseUser->delete();
    }
}
