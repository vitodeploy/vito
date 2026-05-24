<?php

namespace App\Tooling;

class BunTooling extends AbstractMiseTooling
{
    public static function id(): string
    {
        return 'bun';
    }

    public static function label(): string
    {
        return 'Bun';
    }

    public static function description(): string
    {
        return 'Fast all-in-one JavaScript runtime and toolkit, used as an alternative to Node.js.';
    }

    public static function supportedVersions(): array
    {
        return ['1.2', '1.1', '1.0'];
    }

    public static function commands(): array
    {
        return ['bun', 'bunx'];
    }
}
