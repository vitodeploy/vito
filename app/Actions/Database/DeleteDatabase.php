<?php

namespace App\Actions\Database;

use App\Models\Database;
use App\Models\Server;
use App\Models\Service;

class DeleteDatabase
{
    public function delete(Server $server, Database $database): void
    {
        /** @var Service $service */
        $service = $server->database();
        /** @var \App\SSH\Services\Database\Database $handler */
        $handler = $service->handler();
        $handler->delete($database->name);
        $database->delete();
    }
}
