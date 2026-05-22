<?php

namespace App\Actions\Bootstrap;

use App\Models\GithubApp;
use Illuminate\Support\Facades\Cache;

final class GetBootstrap
{
    public const string VERSION_CACHE_KEY = 'bootstrap.version';

    /** @var array<string, mixed>|null */
    private ?array $cachedConfigs = null;

    private ?string $cachedPublicKeyText = null;

    /**
     * @return array{
     *     version: string,
     *     configs: array<string, mixed>,
     *     public_key_text: string,
     * }
     */
    public function handle(): array
    {
        return [
            'version' => $this->version(),
            'configs' => $this->configs(),
            'public_key_text' => $this->publicKeyText(),
        ];
    }

    public function version(): string
    {
        if (! app()->isProduction()) {
            return $this->computeVersion();
        }

        return Cache::rememberForever(
            self::VERSION_CACHE_KEY,
            fn (): string => $this->computeVersion(),
        );
    }

    /**
     * Compute the version directly from current in-memory config without
     * touching the cache. Used by plugin lifecycle Actions immediately after
     * mutating the catalogue, so the broadcast payload reflects this request's
     * fresh state instead of a value a concurrent request may have re-cached.
     */
    public function computeVersion(): string
    {
        return substr(md5(serialize($this->configs()).'|'.$this->publicKeyText()), 0, 16);
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
        return $this->cachedConfigs ??= [
            'operating_systems' => config('core.operating_systems'),
            'colors' => config('core.colors'),
            'cronjob_intervals' => config('core.cronjob_intervals'),
            'metrics_periods' => config('core.metrics_periods'),
            'site' => [
                'types' => config('site.types'),
                'reserved_user_names' => config('core.reserved_user_names'),
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
            'github_app' => [
                'installed' => GithubApp::query()->exists(),
            ],
        ];
    }

    private function publicKeyText(): string
    {
        return $this->cachedPublicKeyText ??= __('servers.create.public_key_text', ['public_key' => get_public_key_content()]);
    }
}
