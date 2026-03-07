<?php

namespace App\Actions\WebSockets;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GenerateWebSocketToken
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{token: string}
     */
    public function generate(string $prefix, array $data, int $ttl = 60): array
    {
        $token = Str::random(64);

        Cache::put("{$prefix}:{$token}", $data, $ttl);

        return ['token' => $token];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function validate(string $prefix, string $token): ?array
    {
        return Cache::pull("{$prefix}:{$token}");
    }
}
