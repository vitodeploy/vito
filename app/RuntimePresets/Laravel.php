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

    public function attachmentAliases(\App\Models\EnvironmentAttachment $attachment, array $generic): array
    {
        $prefix = $attachment->alias !== null ? $attachment->alias.'_' : '';
        if ($attachment->kind() === 'redis') {
            return [$prefix.'REDIS_PREFIX' => \Illuminate\Support\Str::slug($attachment->environment->application->name, '_').'_'.$attachment->environment->slug.'_'];
        }

        return [
            $prefix.'DB_CONNECTION' => $attachment->service()?->name === 'postgresql' ? 'pgsql' : 'mysql',
            $prefix.'DB_HOST' => $generic[$prefix.'DATABASE_HOST'],
            $prefix.'DB_PORT' => $generic[$prefix.'DATABASE_PORT'],
            $prefix.'DB_DATABASE' => $generic[$prefix.'DATABASE_NAME'],
            $prefix.'DB_USERNAME' => $generic[$prefix.'DATABASE_USER'],
            $prefix.'DB_PASSWORD' => $generic[$prefix.'DATABASE_PASSWORD'],
        ];
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
