<?php

namespace App\StorageProviders;

use App\Contracts\StorageProvider as StorageProviderContract;
use App\Models\StorageProvider;

abstract class AbstractStorageProvider implements StorageProviderContract
{
    protected StorageProvider $storageProvider;

    public function __construct(StorageProvider $storageProvider)
    {
        $this->storageProvider = $storageProvider;
    }
}
