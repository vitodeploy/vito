<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSH\Storage\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Dropbox extends AbstractStorageProvider
{
    private const int TOKEN_TTL = 10800;

    protected string $apiUrl = 'https://api.dropboxapi.com/2';

    protected string $tokenUrl = 'https://api.dropbox.com/oauth2/token';

    public static function id(): string
    {
        return 'dropbox';
    }

    public function validationRules(): array
    {
        return [
            'app_key' => 'required',
            'app_secret' => 'required',
            'refresh_token' => 'required',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'app_key' => $input['app_key'],
            'app_secret' => $input['app_secret'],
            'refresh_token' => $input['refresh_token'],
        ];
    }

    public function accessToken(): string
    {
        if (! $this->storageProvider->id) {
            return $this->fetchAccessToken();
        }

        return Cache::remember(
            $this->tokenCacheKey(),
            self::TOKEN_TTL,
            fn (): string => $this->fetchAccessToken()
        );
    }

    public function forgetAccessToken(): void
    {
        if ($this->storageProvider->id) {
            Cache::forget($this->tokenCacheKey());
        }
    }

    public function connect(): bool
    {
        $res = Http::withToken($this->accessToken())
            ->post($this->apiUrl.'/check/user', [
                'query' => '',
            ]);

        return $res->successful();
    }

    private function tokenCacheKey(): string
    {
        return "dropbox_token_{$this->storageProvider->id}";
    }

    private function fetchAccessToken(): string
    {
        $credentials = $this->storageProvider->credentials;

        if (! isset($credentials['app_key'], $credentials['app_secret'], $credentials['refresh_token'])) {
            throw new RuntimeException('Dropbox credentials are incomplete, please reconnect.');
        }

        $res = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credentials['refresh_token'],
            'client_id' => $credentials['app_key'],
            'client_secret' => $credentials['app_secret'],
        ]);

        if (! $res->successful()) {
            throw new RuntimeException("Failed to refresh Dropbox access token (HTTP {$res->status()})");
        }

        $accessToken = $res->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Dropbox did not return an access token.');
        }

        return $accessToken;
    }

    public function ssh(Server $server): Storage
    {
        return new \App\SSH\Storage\Dropbox($server, $this->storageProvider);
    }
}
