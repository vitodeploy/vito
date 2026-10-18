<?php

namespace App\RuntimePresets;

use App\Models\Environment;

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

    public function warnings(Environment $environment): array
    {
        if ($environment->components()->whereHas('capabilities', fn ($query) => $query->where('capability', 'web'))->count() <= 1) {
            return [];
        }

        return [
            'File sessions are local to each web component. Use a shared session driver when serving traffic from multiple components.',
            'Files on the local disk are not shared between web components. Use shared storage for uploads.',
        ];
    }
}
