<?php

namespace App\Actions\Database;

use App\Enums\DatabaseUserStatus;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\Models\Service;
use App\Services\Database\Database;

class SyncDatabaseUsers
{
    public function sync(Server $server): void
    {
        $service = $server->database();
        if (! $service instanceof Service) {
            return;
        }
        /** @var Database $handler */
        $handler = $service->handler();

        $this->updateUsers($server, $handler);
    }

    private function updateUsers(Server $server, Database $handler): void
    {
        $users = $handler->getUsers();
        foreach ($users as $user) {
            $databases = $user[2] != 'NULL' ? explode(',', $user[2]) : [];

            $query = $server->databaseUsers()->where('username', $user[0]);

            // MySQL/MariaDB distinguish users by (username, host); match both to avoid
            // collapsing distinct accounts onto a single row. PostgreSQL's get-users-list
            // returns an empty host (roles are host-agnostic), so only filter when present.
            if ($user[1] !== '') {
                $query->where('host', $user[1]);
            }

            /** @var ?DatabaseUser $databaseUser */
            $databaseUser = $query->first();

            if ($databaseUser === null) {
                $server->databaseUsers()->create([
                    'username' => $user[0],
                    'host' => $user[1],
                    'databases' => $databases,
                    'status' => DatabaseUserStatus::READY,
                ]);

                continue;
            }

            $databaseUser->databases = $databases;
            $databaseUser->save();
        }
    }
}
