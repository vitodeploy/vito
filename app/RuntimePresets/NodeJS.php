<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;
use App\Support\RepositoryFiles;
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

    public function detect(RepositoryFiles $files): ?array
    {
        if (! $files->has('package.json') || $files->has('composer.json')) {
            return null;
        }
        $engine = $files->json('package.json')['engines']['node'] ?? null;

        return $this->compatibleVersions(($files->get('.nvmrc') ?: $files->get('.node-version')) ?: (is_string($engine) ? $engine : null));
    }
}
