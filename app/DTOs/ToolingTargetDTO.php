<?php

namespace App\DTOs;

use App\Models\Component;
use App\Models\IsolatedUser;
use App\Models\Server;
use App\Models\Site;

final readonly class ToolingTargetDTO
{
    public function __construct(
        public Server $server,
        public string $user,
        public ?IsolatedUser $isolatedUser = null,
        public ?string $phpVersion = null,
        public ?int $siteId = null,
        public ?string $configDir = null,
    ) {}

    public static function forComponent(Component $component): self
    {
        return new self(
            server: $component->server,
            user: $component->user,
            isolatedUser: $component->isolatedUser,
            phpVersion: $component->environment->runtime_version,
            configDir: $component->path,
        );
    }

    public static function forSite(Site $site): self
    {
        return new self(
            server: $site->server,
            user: $site->user ?? '',
            isolatedUser: $site->isolatedUser,
            phpVersion: $site->php_version,
            siteId: $site->id,
        );
    }
}
