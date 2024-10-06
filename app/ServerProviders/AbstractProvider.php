<?php

namespace App\ServerProviders;

use App\Models\Server;
use App\Models\ServerProvider as Provider;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

abstract class AbstractProvider implements ServerProvider
{
    protected ?Provider $serverProvider;

    protected ?Server $server;

    public function __construct(?Provider $serverProvider = null, ?Server $server = null)
    {
        $this->serverProvider = $serverProvider;
        $this->server = $server;
    }

    public function generateKeyPair(): void
    {
        /** @var FilesystemAdapter $storageDisk */
        $storageDisk = Storage::disk(config('core.key_pairs_disk'));
        generate_key_pair($storageDisk->path((string) $this->server->id));
    }
}
