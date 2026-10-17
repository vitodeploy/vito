<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;

class PHP extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'php';
    }

    public static function label(): string
    {
        return 'PHP';
    }

    public function versions(): array
    {
        return config('service.services.php.versions', []);
    }

    public function requiredServices(string $version): array
    {
        return ['php' => $version];
    }

    public function webMode(): WebMode
    {
        return WebMode::FPM;
    }
}
