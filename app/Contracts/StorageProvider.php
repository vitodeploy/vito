<?php

namespace App\Contracts;

use App\Models\Server;
use Symfony\Component\HttpFoundation\RedirectResponse;

interface StorageProvider
{
    public function connect(): RedirectResponse;

    public function upload(Server $server, string $src, string $dest): array;

    public function download(Server $server, string $src, string $dest): void;

    public function delete(array $paths): void;
}
