<?php

namespace App\DTOs;

use App\Models\Server;

final readonly class GitCloneDTO
{
    /**
     * @param  array<string, string>  $variables
     */
    public function __construct(
        public Server $server,
        public string $user,
        public string $repoUrl,
        public string $branch,
        public string $path,
        public string $keyName,
        public int $port = 22,
        public bool $usesToken = false,
        public array $variables = [],
        public ?string $commit = null,
        public ?int $siteId = null,
    ) {}
}
