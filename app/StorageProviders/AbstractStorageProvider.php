<?php

namespace App\StorageProviders;

use App\Models\StorageProvider;
use App\StorageProviders\StorageProvider as StorageProviderContract;

abstract class AbstractStorageProvider implements StorageProviderContract
{
    protected StorageProvider $storageProvider;

    public function __construct(StorageProvider $storageProvider)
    {
        $this->storageProvider = $storageProvider;
    }
}
