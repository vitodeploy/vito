<?php

namespace App\Jobs\Database;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Database;

class DeleteFromServer extends Job
{
    protected Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function handle(): void
    {
        $this->database->server->database()->handler()->delete($this->database->name);
        event(
            new Broadcast('delete-database-finished', [
                'id' => $this->database->id,
            ])
        );
        $this->database->delete();
    }

    public function failed(): void
    {
        event(
            new Broadcast('delete-database-failed', [
                'id' => $this->database->id,
            ])
        );
    }
}
