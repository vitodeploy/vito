<?php

namespace App\Actions\StorageProvider;

use App\Models\StorageProvider;
use App\StorageProviders\Dropbox;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DeleteStorageProvider
{
    public function delete(StorageProvider $storageProvider): void
    {
        if ($storageProvider->backups()->exists()) {
            throw ValidationException::withMessages([
                'provider' => __('This storage provider is being used by a backup.'),
            ]);
        }

        if ($storageProvider->provider === Dropbox::id()) {
            Cache::forget("dropbox_token_{$storageProvider->id}");
        }

        $storageProvider->delete();
    }
}
