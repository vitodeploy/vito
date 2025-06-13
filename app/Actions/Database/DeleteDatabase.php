<?php

namespace App\Actions\Database;

use App\Models\Backup;
use App\Models\Database;
use App\Models\Server;
use App\Models\Service;

class DeleteDatabase
{
    public function delete(Server $server, Database $database): void
    {
        /** @var Service $service */
        $service = $server->database();
        /** @var \App\Services\Database\Database $handler */
        $handler = $service->handler();
        $handler->delete($database->name);
        $database->delete();

        $database->backups()->each(function (Backup $backup): void {
            app(ManageBackup::class)->stop($backup);
        });
    }
}
