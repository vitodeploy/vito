<?php

namespace App\Tooling;

class NodeTooling extends MiseTooling
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
        return ['22', '23', '24'];
    }
}
