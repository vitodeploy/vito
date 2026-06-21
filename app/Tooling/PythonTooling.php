<?php

namespace App\Tooling;

class PythonTooling extends AbstractMiseTooling
{
    public static function id(): string
    {
        return 'python';
    }

    public static function label(): string
    {
        return 'Python';
    }

    public static function description(): string
    {
        return 'Python runtime used to run Python applications and tools during deployment.';
    }

    public static function supportedVersions(): array
    {
        return ['3.14', '3.13', '3.12', '3.11'];
    }

    public static function commands(): array
    {
        return ['python', 'python3', 'pip', 'pip3'];
    }
}
