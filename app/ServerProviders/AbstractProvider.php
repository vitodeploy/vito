<?php

namespace App\ServerProviders;

use App\Models\Server;
use App\Models\ServerProvider as Provider;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

abstract class AbstractProvider implements ServerProvider
{
    public function __construct(protected Provider $serverProvider, protected Server $server) {}

    /**
     * @return array<string, string>
     */
    public function availablePlans(?string $region): array
    {
        return collect($this->plans($region))
            ->reject(fn (mixed $plan): bool => is_array($plan) && ($plan['available'] ?? true) === false)
            ->map(fn (mixed $plan): string => is_array($plan) ? $plan['label'] : $plan)
            ->all();
    }

    public function generateKeyPair(): void
    {
        /** @var FilesystemAdapter $storageDisk */
        $storageDisk = Storage::disk(config('core.key_pairs_disk'));
        generate_key_pair($storageDisk->path((string) $this->server->id));
    }
}
