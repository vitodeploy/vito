<?php

namespace App\Jobs\EnvironmentDomain;

use App\Actions\EnvironmentDomain\CheckEnvironmentDomain;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckEnvironmentDomainJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 300;

    public function __construct(protected int $domainId, protected int $serverId)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run("server-{$this->serverId}", fn () => app(CheckEnvironmentDomain::class)->check($this->domainId, $this->serverId));
    }

    public function failed(Exception $e): void
    {
        app(CheckEnvironmentDomain::class)->failed($this->domainId, $this->serverId);
    }
}
