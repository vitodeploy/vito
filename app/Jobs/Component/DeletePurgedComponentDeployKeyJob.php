<?php

namespace App\Jobs\Component;

use App\Actions\Component\PurgeServerComponents;
use App\Actions\Component\SyncComponentDeployKeys;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeletePurgedComponentDeployKeyJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected int $componentId,
        protected int $serverId,
        protected int $environmentId,
        protected ?int $sourceControlId,
        protected ?string $repository,
        protected ?string $keyId,
        protected ?string $publicKey = null,
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->serverId}", fn () => app(SyncComponentDeployKeys::class)->removeProviderKey($this->sourceControlId, $this->repository, $this->keyId, $this->publicKey));
    }

    public function failed(Exception $e): void
    {
        app(PurgeServerComponents::class)->failed($this->environmentId);
    }
}
