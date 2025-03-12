<?php

namespace App\StorageProviders;

use App\Models\StorageProvider;
use App\StorageProviders\StorageProvider as StorageProviderContract;

abstract class AbstractStorageProvider implements StorageProviderContract
{
    public function __construct(protected StorageProvider $storageProvider) {}
}
