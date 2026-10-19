<?php

namespace App\Actions\Database;

use App\Models\DatabaseUser;
use App\Models\Server;
use App\Models\Service;
use App\Services\Database\Database;

class DeleteDatabaseUser
{
    public function delete(Server $server, DatabaseUser $databaseUser, ?\App\Models\EnvironmentAttachment $attachment = null): void
    {
        app(\App\Actions\EnvironmentAttachment\ValidateEnvironmentResource::class)->databaseUser($databaseUser, $attachment);

        /** @var Service $service */
        $service = $server->database();
        /** @var Database $handler */
        $handler = $service->handler();
        $handler->deleteUser($databaseUser->username, $databaseUser->host);
        $databaseUser->delete();
    }
}
