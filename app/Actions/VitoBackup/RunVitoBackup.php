<?php

namespace App\Actions\VitoBackup;

use App\Enums\VitoBackupFileStatus;
use App\Enums\VitoBackupStatus;
use App\Models\VitoBackup;
use App\Models\VitoBackupFile;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RunVitoBackup
{
    use BackupOperations, StorageOperations;

    /**
     * @throws Exception
     */
    public function run(VitoBackup $vitoBackup): void
    {
        DB::transaction(function () use ($vitoBackup) {
            $vitoBackup->status = VitoBackupStatus::RUNNING;
            $vitoBackup->save();

            try {
                $backupName = 'vito-backup-'.date('Y-m-d-H-i-s').'.zip';
                $backupFile = $this->createBackupFile($vitoBackup, $backupName);

                $zipPath = $this->export($backupName);

                // Upload to storage provider
                $this->uploadToStorage($vitoBackup, $zipPath, $backupFile);

                // Clean up old backups
                $this->cleanupOldBackups($vitoBackup);

                $vitoBackup->status = VitoBackupStatus::SUCCESS;
                $vitoBackup->save();

            } catch (Exception $e) {
                $vitoBackup->status = VitoBackupStatus::FAILED;
                $vitoBackup->save();
                throw $e;
            }
        });
    }

    private function createBackupFile(VitoBackup $vitoBackup, string $backupName): VitoBackupFile
    {
        $backupFile = new VitoBackupFile([
            'vito_backup_id' => $vitoBackup->id,
            'name' => $backupName,
            'size' => 0,
            'status' => VitoBackupFileStatus::CREATING,
        ]);
        $backupFile->save();

        return $backupFile;
    }

    private function cleanupOldBackups(VitoBackup $vitoBackup): void
    {
        $keepBackups = $vitoBackup->keep_backups;
        $files = $vitoBackup->files()->orderByDesc('created_at')->get();

        if ($files->count() > $keepBackups) {
            $filesToDelete = $files->skip($keepBackups);

            foreach ($filesToDelete as $file) {
                $file->status = VitoBackupFileStatus::DELETING;
                $file->save();

                // Delete from storage provider
                try {
                    $this->deleteFromStorage($vitoBackup, $file);
                } catch (Exception $e) {
                    // Log error but continue with cleanup
                    \Illuminate\Support\Facades\Log::error('Failed to delete backup file from storage: '.$e->getMessage(), [
                        'file_id' => $file->id,
                        'file_path' => $file->path,
                    ]);
                }

                // Delete from database
                $file->delete();
            }
        }
    }
}
