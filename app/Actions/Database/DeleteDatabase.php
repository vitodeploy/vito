<?php

namespace App\Actions\Database;

use App\Models\Database;
use App\Models\Server;

class DeleteDatabase
{
    public function delete(Server $server, Database $database): void
    {
        $server->database()->handler()->delete($database->name);
        $database->delete();
    }
}
