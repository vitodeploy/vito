<?php

namespace App\Jobs\EnvironmentDomain;

use App\Actions\Environment\DeleteEnvironment;
use App\Actions\EnvironmentDomain\DeleteEnvironmentDomain;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteEnvironmentDomainsJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected int $environmentId, protected int $serverId)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run("server-{$this->serverId}", fn () => app(DeleteEnvironmentDomain::class)->removeForServer($this->environmentId, $this->serverId));
    }

    public function failed(Exception $e): void
    {
        app(DeleteEnvironment::class)->failed($this->environmentId);
    }
}
