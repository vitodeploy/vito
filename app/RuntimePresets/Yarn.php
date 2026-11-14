<?php

namespace App\RuntimePresets;

use App\Enums\NodePackageManager;
use App\Support\RepositoryFiles;
use App\Tooling\YarnTooling;

class Yarn extends NodeJS
{
    public static function id(): string
    {
        return 'yarn';
    }

    public static function label(): string
    {
        return 'Node.js (Yarn)';
    }

    public function additionalTooling(): array
    {
        return [YarnTooling::id() => YarnTooling::supportedVersions()[0]];
    }

    public function startCommand(): ?string
    {
        return NodePackageManager::Yarn->startCommand();
    }

    public function detect(RepositoryFiles $files): ?array
    {
        $manager = $files->json('package.json')['packageManager'] ?? null;
        $matches = is_string($manager) && $manager !== ''
            ? str_starts_with($manager, 'yarn@')
            : $files->has('yarn.lock');

        return $matches ? parent::detect($files) : null;
    }
}
