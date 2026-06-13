<?php

namespace App\Actions\Server;

use App\Enums\ServerStatus;
use App\Jobs\Server\UpdateKernelJob;
use App\Models\Server;

class UpdateKernel
{
    public function updateKernel(Server $server): void
    {
        $server->status = ServerStatus::UPDATING;
        $server->save();
        dispatch(new UpdateKernelJob($server))->onQueue('ssh');
    }
}
