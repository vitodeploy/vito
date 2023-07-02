<?php

namespace App\Jobs\DatabaseUser;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\DatabaseUser;

class DeleteFromServer extends Job
{
    protected DatabaseUser $databaseUser;

    public function __construct(DatabaseUser $databaseUser)
    {
        $this->databaseUser = $databaseUser;
    }

    public function handle(): void
    {
        $this->databaseUser->server->database()->handler()->deleteUser(
            $this->databaseUser->username,
            $this->databaseUser->host
        );
        event(
            new Broadcast('delete-database-user-finished', [
                'id' => $this->databaseUser->id,
            ])
        );
        $this->databaseUser->delete();
    }

    public function failed(): void
    {
        event(
            new Broadcast('delete-database-user-failed', [
                'id' => $this->databaseUser->id,
            ])
        );
    }
}
