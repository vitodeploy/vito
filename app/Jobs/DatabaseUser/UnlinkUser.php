<?php

namespace App\Jobs\DatabaseUser;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\DatabaseUser;

class UnlinkUser extends Job
{
    protected DatabaseUser $databaseUser;

    public function __construct(DatabaseUser $databaseUser)
    {
        $this->databaseUser = $databaseUser;
    }

    public function handle(): void
    {
        $this->databaseUser->server->database()->handler()->unlink(
            $this->databaseUser->username,
            $this->databaseUser->host,
        );
        event(
            new Broadcast('unlink-database-user-finished', [
                'id' => $this->databaseUser->id,
            ])
        );
    }

    public function failed(): void
    {
        event(
            new Broadcast('unlink-database-user-failed', [
                'id' => $this->databaseUser->id,
            ])
        );
    }
}
