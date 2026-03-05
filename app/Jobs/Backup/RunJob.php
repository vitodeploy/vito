<?php

namespace App\Jobs\Backup;

use App\Actions\Backup\RunBackup;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\Database\Database;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected BackupFile $file,
        protected Backup $backup,
    ) {}

    public function handle(): void
    {
        $this->run("backup-{$this->backup->id}", function () {
            if ($this->backup->type === BackupType::DATABASE) {
                /** @var Service $service */
                $service = $this->backup->server->database();
                /** @var Database $databaseHandler */
                $databaseHandler = $service->handler();
                $databaseHandler->runBackup($this->file);
            }

            if ($this->backup->type === BackupType::FILE) {
                app(RunBackup::class)->compressAndUploadFile($this->file, $this->backup);
            }

            $this->file->status = BackupFileStatus::CREATED;
            $this->file->save();

            if ($this->backup->status !== BackupStatus::RUNNING) {
                $this->backup->status = BackupStatus::RUNNING;
                $this->backup->save();
            }
        });
    }

    public function failed(Exception $e): void
    {
        $this->backup->status = BackupStatus::FAILED;
        $this->backup->save();
        $this->file->status = BackupFileStatus::FAILED;
        $this->file->save();
        ServerLog::log($this->backup->server, 'run-backup-failed', $e->getMessage());
    }
}
