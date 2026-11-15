<?php

namespace App\Jobs\Application;

use App\Actions\Application\DeleteApplication;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteApplicationJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected int $applicationId) {}

    public function handle(): void
    {
        $this->run("application-{$this->applicationId}", fn () => app(DeleteApplication::class)->handle($this->applicationId));
    }

    public function failed(Exception $e): void
    {
        app(DeleteApplication::class)->failed($this->applicationId);
    }
}
