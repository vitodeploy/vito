<?php

namespace App\Tooling;

class PnpmTooling extends AbstractMiseTooling
{
    public static function id(): string
    {
        return 'pnpm';
    }

    public static function label(): string
    {
        return 'pnpm';
    }

    public static function description(): string
    {
        return 'Fast, disk space efficient package manager for Node.js projects.';
    }

    public static function supportedVersions(): array
    {
        return ['10', '9', '8'];
    }

    public static function commands(): array
    {
        return ['pnpm', 'pnpx'];
    }
}
