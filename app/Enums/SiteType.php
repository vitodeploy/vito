<?php

namespace App\Enums;

final class SiteType
{
    const PHP = 'php';

    const PHP_BLANK = 'php-blank';

    const LARAVEL = 'laravel';

    const WORDPRESS = 'wordpress';

    const PHPMYADMIN = 'phpmyadmin';

    public static function hasWebDirectory(): array
    {
        return [
            self::PHP,
            self::PHP_BLANK,
            self::LARAVEL,
        ];
    }

    public static function hasSourceControl(): array
    {
        return [
            self::PHP,
            self::LARAVEL,
        ];
    }
}
