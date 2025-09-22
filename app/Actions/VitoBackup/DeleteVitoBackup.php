<?php

namespace App\Actions\VitoBackup;

use App\Enums\VitoBackupStatus;
use App\Models\VitoBackup;
use Exception;

class DeleteVitoBackup
{
    use StorageOperations;

    public function delete(VitoBackup $vitoBackup): void
    {
        $vitoBackup->status = VitoBackupStatus::DELETING;
        $vitoBackup->save();

        // Delete all backup files from storage providers first
        $vitoBackup->files()->each(function ($file) use ($vitoBackup) {
            try {
                // Delete from storage provider
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
        });

        // Then delete the backup itself
        $vitoBackup->delete();
    }
}
