<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use Exception;

class DeleteStorageProvider
{
    /**
     * @throws Exception
     */
    public function delete(StorageProvider $storageProvider): void
    {
        if ($storageProvider->backups()->exists()) {
            throw new Exception('This storage provider is being used by a backup.');
        }

        $storageProvider->delete();
    }
}
