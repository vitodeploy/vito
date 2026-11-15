<?php

namespace App\Jobs\Environment;

use App\Actions\Environment\DeleteEnvironment;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteEnvironmentJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected int $environmentId) {}

    public function handle(): void
    {
        $this->run("environment-{$this->environmentId}", fn () => app(DeleteEnvironment::class)->handle($this->environmentId));
    }

    public function failed(Exception $e): void
    {
        app(DeleteEnvironment::class)->failed($this->environmentId);
    }
}
