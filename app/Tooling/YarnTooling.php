<?php

namespace App\Tooling;

class YarnTooling extends AbstractMiseTooling
{
    public static function id(): string
    {
        return 'yarn';
    }

    public static function label(): string
    {
        return 'Yarn';
    }

    public static function description(): string
    {
        return 'Package manager for Node.js projects. Choose v1 for Classic or v3/v4 for Berry.';
    }

    public static function supportedVersions(): array
    {
        return ['4', '3', '1'];
    }

    public static function commands(): array
    {
        return ['yarn'];
    }
}
