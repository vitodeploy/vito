<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;
use App\Tooling\NodeTooling;

class NodeJS extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'node';
    }

    public static function label(): string
    {
        return 'Node.js';
    }

    public function versions(): array
    {
        return NodeTooling::supportedVersions();
    }

    public function tooling(): ?string
    {
        return NodeTooling::id();
    }

    public function webMode(): WebMode
    {
        return WebMode::PROXY;
    }

    public function startCommand(): ?string
    {
        return 'npm start';
    }
}
