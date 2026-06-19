<?php

namespace App\Console\Commands;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Events\SocketEvent;
use App\Facades\Notifier;
use App\Http\Resources\BackupFileResource;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Notifications\BackupFailed;
use App\Notifications\FailedToDeleteBackupFileFromProvider;
use App\Notifications\RestoreFailed;
use Illuminate\Console\Command;

class ReconcileBackupsCommand extends Command
{
    protected $signature = 'backups:reconcile';

    protected $description = 'Fail backup files stuck in a transient state after an interrupted job';

    public function handle(): void
    {
        // Intentionally conservative: a window of 2x the longest backup timeout
        // guarantees no in-flight dump/restore/delete is ever reaped mid-run.
        $threshold = now()->subSeconds(2 * (int) config('core.backup_run_timeout'));
        $total = 0;

        BackupFile::query()
            ->whereIn('status', [
                BackupFileStatus::CREATING,
                BackupFileStatus::RESTORING,
                BackupFileStatus::DELETING,
            ])
            ->where('updated_at', '<', $threshold)
            ->with('backup.server', 'backup.storage')
            ->chunkById(100, function ($files) use (&$total): void {
                /** @var BackupFile $file */
                foreach ($files as $file) {
                    $this->reconcile($file);
                    $total++;
                }
            });

        Backup::query()
            ->where('status', BackupStatus::DELETING)
            ->where('updated_at', '<', $threshold)
            ->with('server')
            ->chunkById(100, function ($backups): void {
                /** @var Backup $backup */
                foreach ($backups as $backup) {
                    $backup->status = BackupStatus::DELETE_FAILED;
                    $backup->save();

                    SocketEvent::dispatch(new SocketEventDTO(
                        projectId: $backup->server->project_id,
                        type: 'backup.updated',
                        data: new BackupResource($backup),
                    ));
                }
            });

        $this->info("{$total} stuck backup files reconciled");
    }

    private function reconcile(BackupFile $file): void
    {
        $server = $file->backup->server;
        $previous = $file->status;

        $file->status = match ($previous) {
            BackupFileStatus::RESTORING => BackupFileStatus::RESTORE_FAILED,
            BackupFileStatus::DELETING => BackupFileStatus::DELETE_FAILED,
            default => BackupFileStatus::FAILED,
        };
        $file->message = __('Interrupted — no active job. Marked failed by reconciliation.');
        $file->save();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $server->project_id,
            type: 'backup-file.updated',
            data: new BackupFileResource($file),
        ));

        match ($previous) {
            BackupFileStatus::RESTORING => Notifier::send($server, new RestoreFailed($server, $file)),
            BackupFileStatus::DELETING => Notifier::send($server, new FailedToDeleteBackupFileFromProvider($file)),
            default => Notifier::send($server, new BackupFailed($file->backup)),
        };
    }
}
