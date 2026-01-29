<?php

namespace App\Contracts;

use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Contracts\View\View;

interface ServerConnection
{
    public function init(Server $server, ?string $asUser = null): self;

    public function setLog(?ServerLog $log): self;

    public function useLog(string $disk, string $path): self;

    public function asUser(?string $user): self;

    public function connect(bool $sftp = false): void;

    public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null): string;

    public function upload(string $local, string $remote, ?string $owner = null, ?string $log = null, ?int $siteId = null): void;

    public function download(string $local, string $remote, ?string $log = null, ?int $siteId = null): void;

    public function write(string $remotePath, string|View $content, ?string $owner = null, ?string $log = null, ?int $siteId = null): void;

    public function disconnect(): void;
}
