<?php

namespace App\Actions\Database;

use App\Actions\EnvironmentAttachment\ValidateEnvironmentResource;
use App\Models\DatabaseUser;
use App\Models\EnvironmentAttachment;
use App\Models\Server;
use App\Models\Service;
use App\Services\Database\Database;

class DeleteDatabaseUser
{
    public function delete(Server $server, DatabaseUser $databaseUser, ?EnvironmentAttachment $attachment = null): void
    {
        app(ValidateEnvironmentResource::class)->databaseUser($databaseUser, $attachment);

        /** @var Service $service */
        $service = $server->database();
        /** @var Database $handler */
        $handler = $service->handler();
        $handler->deleteUser($databaseUser->username, $databaseUser->host);
        $databaseUser->delete();
    }
}
