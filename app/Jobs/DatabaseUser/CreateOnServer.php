<?php

namespace App\Jobs\DatabaseUser;

use App\Enums\DatabaseUserStatus;
use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\DatabaseUser;

class CreateOnServer extends Job
{
    protected DatabaseUser $databaseUser;

    public function __construct(DatabaseUser $databaseUser)
    {
        $this->databaseUser = $databaseUser;
    }

    public function handle(): void
    {
        $this->databaseUser->server->database()->handler()->createUser(
            $this->databaseUser->username,
            $this->databaseUser->password,
            $this->databaseUser->host
        );
        $this->databaseUser->status = DatabaseUserStatus::READY;
        $this->databaseUser->save();
        event(
            new Broadcast('create-database-user-finished', [
                'id' => $this->databaseUser->id,
            ])
        );
    }

    public function failed(): void
    {
        event(
            new Broadcast('create-database-user-failed', [
                'id' => $this->databaseUser->id,
            ])
        );
    }
}
