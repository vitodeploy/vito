<?php

namespace App\Actions\Database;

use App\Models\Database;
use App\Models\Server;

class DeleteDatabase
{
    public function delete(Server $server, Database $database): void
    {
        $service = $server->database();
        if (! $service instanceof \App\Models\Service) {
            throw new \Exception('Database service not found');
        }
        /** @var \App\SSH\Services\Database\Database $handler */
        $handler = $service->handler();
        $handler->delete($database->name);
        $database->delete();
    }
}
