<?php

namespace App\SSH\Storage;

use App\Models\Server;
use App\Models\StorageProvider;

abstract class AbstractStorage implements Storage
{
    public function __construct(protected Server $server, protected StorageProvider $storageProvider) {}
}
