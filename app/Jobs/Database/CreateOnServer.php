<?php

namespace App\Jobs\Database;

use App\Enums\DatabaseStatus;
use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Database;

class CreateOnServer extends Job
{
    protected Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function handle(): void
    {
        $this->database->server->database()->handler()->create($this->database->name);
        $this->database->status = DatabaseStatus::READY;
        $this->database->save();
        event(new Broadcast('create-database-finished', [
            'id' => $this->database->id,
        ]));
    }

    public function failed(): void
    {
        event(new Broadcast('create-database-failed', [
            'id' => $this->database->id,
        ]));
    }
}
