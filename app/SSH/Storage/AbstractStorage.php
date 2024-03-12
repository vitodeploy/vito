<?php

namespace App\SSH\Storage;

use App\Models\Server;
use App\Models\StorageProvider;
use App\SSH\Storage\Storage;

abstract class AbstractStorage implements Storage
{
    public function __construct(protected Server $server, protected StorageProvider $storageProvider)
    {
    }
}
