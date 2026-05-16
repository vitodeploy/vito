<?php

namespace App\Actions\Bootstrap;

use Illuminate\Support\Facades\Cache;

final readonly class GetBootstrap
{
    public const string VERSION_CACHE_KEY = 'bootstrap.version';

    /**
     * @return array{
     *     version: string,
     *     configs: array<string, mixed>,
     *     public_key_text: string,
     * }
     */
    public function handle(): array
    {
        $configs = $this->configs();
        $publicKeyText = $this->publicKeyText();

        return [
            'version' => $this->version(),
            'configs' => $configs,
            'public_key_text' => $publicKeyText,
        ];
    }

    public function version(): string
    {
        return Cache::rememberForever(
            self::VERSION_CACHE_KEY,
            fn (): string => substr(md5(serialize($this->configs()).'|'.$this->publicKeyText()), 0, 16),
        );
    }

    public static function forgetVersion(): void
    {
        Cache::forget(self::VERSION_CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function configs(): array
    {
        return [
            'operating_systems' => config('core.operating_systems'),
            'colors' => config('core.colors'),
            'cronjob_intervals' => config('core.cronjob_intervals'),
            'metrics_periods' => config('core.metrics_periods'),
            'site' => [
                'types' => config('site.types'),
            ],
            'source_control' => [
                'providers' => config('source-control.providers'),
            ],
            'server_provider' => [
                'providers' => config('server-provider.providers'),
            ],
            'storage_provider' => [
                'providers' => config('storage-provider.providers'),
            ],
            'notification_channel' => [
                'providers' => config('notification-channel.providers'),
            ],
            'service' => [
                'services' => config('service.services'),
            ],
            'dns_provider' => [
                'providers' => config('dns-provider.providers'),
            ],
        ];
    }

    private function publicKeyText(): string
    {
        return __('servers.create.public_key_text', ['public_key' => get_public_key_content()]);
    }
}
