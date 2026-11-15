<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;
use App\Support\RepositoryFiles;
use App\Tooling\ComposerTooling;

class PHP extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'php';
    }

    public static function label(): string
    {
        return 'PHP';
    }

    public function versions(): array
    {
        return config('service.services.php.versions', []);
    }

    public function requiredServices(string $version): array
    {
        return ['php' => $version];
    }

    public function tooling(): ?string
    {
        return ComposerTooling::id();
    }

    public function sharedTooling(): bool
    {
        return false;
    }

    public function toolingVersion(string $version): string
    {
        return '2';
    }

    public function executables(string $version): array
    {
        return ['php' => '/usr/bin/php'.$version];
    }

    public function webMode(): WebMode
    {
        return WebMode::FPM;
    }

    public function detect(RepositoryFiles $files): ?array
    {
        if (! $files->has('composer.json') && ! $files->has('index.php')) {
            return null;
        }
        $constraint = $files->json('composer.json')['require']['php'] ?? null;

        return $this->compatibleVersions(is_string($constraint) ? $constraint : null);
    }
}
