<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use Exception;
use Illuminate\Validation\ValidationException;

class DeleteStorageProvider
{
    /**
     * @throws Exception
     */
    public function delete(StorageProvider $storageProvider): void
    {
        if ($storageProvider->backups()->exists()) {
            throw ValidationException::withMessages([
                'provider' => __('This storage provider is being used by a backup.'),
            ]);
        }

        $storageProvider->delete();
    }
}
