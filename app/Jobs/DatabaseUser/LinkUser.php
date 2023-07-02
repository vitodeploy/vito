<?php

namespace App\Jobs\DatabaseUser;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\DatabaseUser;

class LinkUser extends Job
{
    protected DatabaseUser $databaseUser;

    public function __construct(DatabaseUser $databaseUser)
    {
        $this->databaseUser = $databaseUser;
    }

    public function handle(): void
    {
        $this->databaseUser->server->database()->handler()->link(
            $this->databaseUser->username,
            $this->databaseUser->host,
            $this->databaseUser->databases
        );
        event(
            new Broadcast('link-database-user-finished', [
                'id' => $this->databaseUser->id,
            ])
        );
    }

    public function failed(): void
    {
        event(
            new Broadcast('link-database-user-failed', [
                'id' => $this->databaseUser->id,
            ])
        );
    }
}
