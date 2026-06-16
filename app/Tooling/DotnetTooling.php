<?php

namespace App\Tooling;

class DotnetTooling extends AbstractMiseTooling
{
    public static function id(): string
    {
        return 'dotnet';
    }

    public static function label(): string
    {
        return '.NET';
    }

    public static function description(): string
    {
        return '.NET SDK used to build and run ASP.NET applications during deployment.';
    }

    public static function supportedVersions(): array
    {
        return ['10', '9', '8'];
    }

    public static function commands(): array
    {
        return ['dotnet'];
    }
}
