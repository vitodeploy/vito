<?php

namespace App\ServerProviders;

use App\Models\Server;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

abstract class AbstractProvider implements ServerProvider
{
    protected ?Server $server;

    public function __construct(?Server $server = null)
    {
        $this->server = $server;
    }

    public function generateKeyPair(): void
    {
        /** @var FilesystemAdapter $storageDisk */
        $storageDisk = Storage::disk(config('core.key_pairs_disk'));
        generate_key_pair($storageDisk->path((string) $this->server->id));
    }
}
