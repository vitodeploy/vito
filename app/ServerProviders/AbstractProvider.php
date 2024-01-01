<?php

namespace App\ServerProviders;

use App\Contracts\ServerProvider;
use App\Models\Server;
use Illuminate\Support\Facades\Storage;

abstract class AbstractProvider implements ServerProvider
{
    protected ?Server $server;

    public function __construct(?Server $server = null)
    {
        $this->server = $server;
    }

    protected function generateKeyPair(): void
    {
        generate_key_pair(Storage::disk(config('core.key_pairs_disk'))->path((string) $this->server->id));
    }
}
