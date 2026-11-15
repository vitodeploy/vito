<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;
use App\Support\RepositoryFiles;
use App\Tooling\PythonTooling;

class Python extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'python';
    }

    public static function label(): string
    {
        return 'Python';
    }

    public function versions(): array
    {
        return PythonTooling::supportedVersions();
    }

    public function tooling(): ?string
    {
        return PythonTooling::id();
    }

    public function webMode(): WebMode
    {
        return WebMode::PROXY;
    }

    public function startCommand(): ?string
    {
        return '.venv/bin/python main.py';
    }

    public function detect(RepositoryFiles $files): ?array
    {
        if ((! $files->has('requirements.txt') && ! $files->has('pyproject.toml') && ! $files->has('setup.py')) || $files->has('composer.json')) {
            return null;
        }

        return $this->compatibleVersions($files->get('.python-version') ?? $files->tomlString('pyproject.toml', 'project', 'requires-python'));
    }
}
