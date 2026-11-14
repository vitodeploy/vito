<?php

namespace App\Actions\Database;

use App\Actions\Backup\ManageBackup;
use App\Models\Backup;
use App\Models\Database;
use App\Models\EnvironmentAttachment;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DeleteDatabase
{
    public function delete(Server $server, Database $database): void
    {
        try {
            Cache::lock('unique-queue:server-'.$server->id, 600)->block(10, function () use ($server, $database): void {
                $database->refresh();
                if (EnvironmentAttachment::query()->whereMorphedTo('attachable', $database)->exists()) {
                    throw ValidationException::withMessages(['database' => 'Detach this database from its environments and finish cleanup before deleting it.']);
                }

                /** @var Service $service */
                $service = $server->database();
                /** @var \App\Services\Database\Database $handler */
                $handler = $service->handler();
                $handler->delete($database->name);
                $database->delete();

                $database->backups()->each(function (Backup $backup): void {
                    app(ManageBackup::class)->stop($backup);
                });
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['database' => 'Another operation on this server is in progress. Please retry.']);
        }
    }
}
