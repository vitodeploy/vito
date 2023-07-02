<?php

namespace App\Jobs\StorageProvider;

use App\Jobs\Job;
use App\Models\StorageProvider;

class DeleteFile extends Job
{
    protected StorageProvider $storageProvider;

    protected array $paths;

    public function __construct(StorageProvider $storageProvider, array $paths)
    {
        $this->storageProvider = $storageProvider;
        $this->paths = $paths;
    }

    public function handle(): void
    {
        $this->storageProvider->provider()->delete($this->paths);
    }
}
