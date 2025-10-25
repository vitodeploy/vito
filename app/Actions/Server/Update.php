<?php

namespace App\Actions\Server;

use App\Enums\ServerStatus;
use App\Jobs\Server\UpdateJob;
use App\Models\Server;

class Update
{
    public function update(Server $server): void
    {
        $server->status = ServerStatus::UPDATING;
        $server->save();
        dispatch(new UpdateJob($server))->onQueue('ssh');
    }
}
