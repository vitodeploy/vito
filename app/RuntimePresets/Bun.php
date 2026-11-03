<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;
use App\Support\RepositoryFiles;
use App\Tooling\BunTooling;

class Bun extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'bun';
    }

    public static function label(): string
    {
        return 'Bun';
    }

    public function versions(): array
    {
        return BunTooling::supportedVersions();
    }

    public function tooling(): ?string
    {
        return BunTooling::id();
    }

    public function webMode(): WebMode
    {
        return WebMode::PROXY;
    }

    public function startCommand(): ?string
    {
        return 'bun run start';
    }

    public function detect(RepositoryFiles $files): ?array
    {
        return ($files->has('bun.lock') || $files->has('bun.lockb')) && ! $files->has('composer.json') ? $this->compatibleVersions(null) : null;
    }
}
