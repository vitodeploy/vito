<?php

namespace App\Tooling;

class GoTooling extends AbstractMiseTooling
{
    public static function id(): string
    {
        return 'go';
    }

    public static function label(): string
    {
        return 'Go';
    }

    public static function description(): string
    {
        return 'Go toolchain used to build Go applications during deployment.';
    }

    public static function supportedVersions(): array
    {
        return ['1.27', '1.26', '1.25'];
    }

    public static function commands(): array
    {
        return ['go', 'gofmt'];
    }
}
