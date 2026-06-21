<?php

namespace App\Console\Commands;

use App\Actions\Backup\BroadcastBackupUpdate;
use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Events\SocketEvent;
use App\Facades\Notifier;
use App\Http\Resources\BackupFileResource;
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

    /**
     * Sweep records stuck in a transient state after an interrupted (e.g. SIGKILL'd)
     * job. The window is intentionally 2x the longest backup timeout so an in-flight
     * dump/restore/delete is never reaped mid-run.
     */
    public function handle(): void
    {
        $threshold = now()->subSeconds(2 * (int) config('core.backup_run_timeout'));
        $files = 0;
        $backups = 0;

        BackupFile::query()
            ->whereIn('status', [
                BackupFileStatus::CREATING,
                BackupFileStatus::RESTORING,
                BackupFileStatus::DELETING,
            ])
            ->where('updated_at', '<', $threshold)
            ->whereHas('backup.server')
            ->with('backup.server', 'backup.storage')
            ->chunkById(100, function ($chunk) use (&$files): void {
                /** @var BackupFile $file */
                foreach ($chunk as $file) {
                    $this->reconcile($file);
                    $files++;
                }
            });

        Backup::query()
            ->where('status', BackupStatus::DELETING)
            ->where('updated_at', '<', $threshold)
            ->whereHas('server')
            ->with('server')
            ->chunkById(100, function ($chunk) use (&$backups): void {
                /** @var Backup $backup */
                foreach ($chunk as $backup) {
                    $backup->status = null;
                    $backup->save();
                    $backups++;

                    app(BroadcastBackupUpdate::class)->broadcast($backup);
                }
            });

        $this->info("{$files} stuck backup files and {$backups} backups reconciled");
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
