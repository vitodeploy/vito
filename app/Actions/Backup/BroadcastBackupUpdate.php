<?php

namespace App\Actions\Backup;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\BackupResource;
use App\Models\Backup;

class BroadcastBackupUpdate
{
    public function broadcast(Backup $backup): void
    {
        $backup->refresh()->load('lastFile');

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $backup->server->project_id,
            type: 'backup.updated',
            data: new BackupResource($backup),
        ));
    }
}
