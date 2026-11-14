<?php

namespace App\Jobs\SSL;

use App\Actions\SSL\RenewEnvironmentSsl;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RenewEnvironmentSslJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 300;

    public function __construct(protected int $sslId, protected int $serverId)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run("server-{$this->serverId}", fn () => app(RenewEnvironmentSsl::class)->renew($this->sslId));
    }

    public function failed(Exception $e): void
    {
        app(RenewEnvironmentSsl::class)->failed($this->sslId);
    }
}
