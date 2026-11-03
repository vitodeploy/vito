<?php

namespace App\RuntimePresets;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Models\EnvironmentVariable;
use App\Support\RepositoryFiles;
use App\Tooling\NodeTooling;
use Closure;
use Illuminate\Support\Str;

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

    public function additionalTooling(): array
    {
        return [NodeTooling::id() => NodeTooling::supportedVersions()[0]];
    }

    public function detect(RepositoryFiles $files): ?array
    {
        return isset($files->json('composer.json')['require']['laravel/framework']) ? parent::detect($files) : null;
    }

    public function suggestsDatabase(): bool
    {
        return true;
    }

    public function creationForm(): DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('app_key')
                ->passwordWithToggle()
                ->label('APP_KEY')
                ->description("Leave empty to generate one. Moving an existing app? Paste its key, or it can't read data encrypted with the old one."),
        ]);
    }

    public function creationRules(): array
    {
        return ['app_key' => ['nullable', 'string', function (string $attribute, string $value, Closure $fail): void {
            $key = str_starts_with($value, 'base64:') ? base64_decode(substr($value, 7), true) : $value;
            if ($key === false || ! in_array(strlen($key), [16, 32], true)) {
                $fail('Enter a valid Laravel key, such as the output of php artisan key:generate --show.');
            }
        }]];
    }

    public function defaultVariables(Environment $environment, array $input): array
    {
        return [
            'APP_KEY' => ['value' => ($input['app_key'] ?? null) ?: 'base64:'.base64_encode(random_bytes(32)), 'is_secret' => true],
            'APP_ENV' => ['value' => $environment->slug, 'is_secret' => false],
            'APP_DEBUG' => ['value' => 'false', 'is_secret' => false],
        ];
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

    public function attachmentAliases(EnvironmentAttachment $attachment, array $generic): array
    {
        $prefix = $attachment->alias !== null ? $attachment->alias.'_' : '';
        if ($attachment->kind() === 'redis') {
            return [$prefix.'REDIS_PREFIX' => Str::slug($attachment->environment->application->name, '_').'_'.$attachment->environment->slug.'_'];
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

        $variables = $environment->variables()->whereIn('key', ['SESSION_DRIVER', 'FILESYSTEM_DISK'])->get()
            ->mapWithKeys(fn (EnvironmentVariable $variable): array => [$variable->key => strtolower(trim($variable->value))]);
        $warnings = [];
        if (! in_array($variables->get('SESSION_DRIVER'), ['redis', 'database', 'memcached', 'dynamodb', 'cookie'], true)) {
            $warnings[] = 'Sessions may be local to each web component. Set SESSION_DRIVER to a shared driver such as redis or database when serving traffic from multiple components.';
        }
        if (in_array($variables->get('FILESYSTEM_DISK'), [null, '', 'local', 'public'], true)) {
            $warnings[] = 'Files on the local disk are not shared between web components. Set FILESYSTEM_DISK to shared storage such as s3 for uploads.';
        }

        return $warnings;
    }
}
