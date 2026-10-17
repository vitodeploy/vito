<?php

namespace App\RuntimePresets;

class Laravel extends PHP
{
    public static function id(): string
    {
        return 'laravel';
    }

    public static function label(): string
    {
        return 'Laravel';
    }

    public function webRoot(): ?string
    {
        return 'public';
    }

    public function schedulerCommand(): ?string
    {
        return 'php artisan schedule:run';
    }

    public function sharedPaths(): array
    {
        return ['storage'];
    }
}
