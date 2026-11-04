<?php

namespace App\Tooling;

class RustTooling extends AbstractMiseTooling
{
    public static function id(): string
    {
        return 'rust';
    }

    public static function label(): string
    {
        return 'Rust';
    }

    public static function description(): string
    {
        return 'Rust toolchain used to build Rust applications during deployment.';
    }

    public static function supportedVersions(): array
    {
        return ['1.99', '1.98', '1.97'];
    }

    public static function commands(): array
    {
        return ['cargo', 'rustc', 'rustup', 'rustfmt'];
    }
}
