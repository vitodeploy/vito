<?php

namespace App\Jobs\EnvironmentDeployment;

use App\Actions\EnvironmentDeployment\TriggerEnvironmentDeployFromWebhook;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TriggerEnvironmentDeployFromWebhookJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(public int $environmentId, public int $sourceControlId, public string $repository, public string $branch) {}

    public function handle(): void
    {
        $this->run('environment-webhook-'.$this->environmentId, fn () => app(TriggerEnvironmentDeployFromWebhook::class)->run(
            $this->environmentId, $this->sourceControlId, $this->repository, $this->branch,
        ));
    }

    public function failed(Exception $e): void
    {
        app(TriggerEnvironmentDeployFromWebhook::class)->failed($this->environmentId);
    }
}
