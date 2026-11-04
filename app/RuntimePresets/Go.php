<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;
use App\Support\RepositoryFiles;
use App\Tooling\GoTooling;

class Go extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'go';
    }

    public static function label(): string
    {
        return 'Go';
    }

    public function versions(): array
    {
        return GoTooling::supportedVersions();
    }

    public function tooling(): ?string
    {
        return GoTooling::id();
    }

    public function webMode(): WebMode
    {
        return WebMode::PROXY;
    }

    public function startCommand(): ?string
    {
        return './app';
    }

    public function detect(RepositoryFiles $files): ?array
    {
        if (! $files->has('go.mod') || $files->has('composer.json')) {
            return null;
        }
        $constraint = $files->get('.go-version');
        if (! $constraint && preg_match('/^go\h+(\d+\.\d+(?:\.\d+)?)/m', $files->get('go.mod') ?? '', $match)) {
            $constraint = '>='.$match[1];
        }

        return $this->compatibleVersions($constraint);
    }
}
