<?php

namespace App\Tooling;

class NodeTooling extends AbstractMiseTooling
{
    public static function id(): string
    {
        return 'node';
    }

    public static function label(): string
    {
        return 'Node.js';
    }

    public static function description(): string
    {
        return 'JavaScript runtime used to build front-end assets and run JavaScript tools during deployment.';
    }

    public static function supportedVersions(): array
    {
        return ['24', '23', '22'];
    }

    public static function commands(): array
    {
        return ['node', 'npm', 'npx'];
    }
}
