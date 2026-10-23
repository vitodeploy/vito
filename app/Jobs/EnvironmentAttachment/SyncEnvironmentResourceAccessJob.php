<?php

namespace App\Jobs\EnvironmentAttachment;

use App\Actions\EnvironmentAttachment\SyncEnvironmentResourceAccess;
use App\Actions\EnvironmentDeployment\DeployEnvironment;
use App\Models\EnvironmentAttachment;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncEnvironmentResourceAccessJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(public int $attachmentId, public int $serverId)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $synchronized = false;
        $this->run('server-'.$this->serverId, function () use (&$synchronized): void {
            $attachment = EnvironmentAttachment::query()->find($this->attachmentId);
            $synchronized = $attachment === null || app(SyncEnvironmentResourceAccess::class)->sync($attachment);
            if (! $synchronized) {
                $this->release(10);
            }
        });
        $attachment = EnvironmentAttachment::query()->find($this->attachmentId);
        if ($synchronized && $attachment) {
            app(DeployEnvironment::class)->join($attachment->environment);
        }
    }

    public function failed(Exception $e): void
    {
        app(SyncEnvironmentResourceAccess::class)->failed($this->attachmentId);
    }
}
