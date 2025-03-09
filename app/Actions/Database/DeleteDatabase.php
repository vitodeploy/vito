<?php

namespace App\Actions\Database;

use App\Models\Database;
use App\Models\Server;

class DeleteDatabase
{
    public function delete(Server $server, Database $database): void
    {
        /** @var \App\SSH\Services\Database\Database $handler */
        $handler = $server->database()->handler();
        $handler->delete($database->name);
        $database->delete();
    }
}
