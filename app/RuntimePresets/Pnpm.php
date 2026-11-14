<?php

namespace App\RuntimePresets;

use App\Enums\NodePackageManager;
use App\Support\RepositoryFiles;
use App\Tooling\PnpmTooling;

class Pnpm extends NodeJS
{
    public static function id(): string
    {
        return 'pnpm';
    }

    public static function label(): string
    {
        return 'Node.js (pnpm)';
    }

    public function additionalTooling(): array
    {
        return [PnpmTooling::id() => PnpmTooling::supportedVersions()[0]];
    }

    public function startCommand(): ?string
    {
        return NodePackageManager::Pnpm->startCommand();
    }

    public function detect(RepositoryFiles $files): ?array
    {
        $manager = $files->json('package.json')['packageManager'] ?? null;
        $matches = is_string($manager) && $manager !== ''
            ? str_starts_with($manager, 'pnpm@')
            : $files->has('pnpm-lock.yaml');

        return $matches ? parent::detect($files) : null;
    }
}
