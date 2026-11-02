<?php

namespace App\Actions\EnvironmentAttachment;

use App\Actions\Environment\MarkEnvironmentForRedeploy;
use App\Enums\EnvironmentAttachmentStatus;
use App\Jobs\EnvironmentAttachment\SyncEnvironmentResourceAccessJob;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use Illuminate\Support\Facades\DB;

class DetachEnvironmentResource
{
    public function detach(EnvironmentAttachment $attachment): void
    {
        DB::transaction(function () use ($attachment): void {
            Application::query()->lockForUpdate()->findOrFail($attachment->environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($attachment->environment_id);
            app(ValidateEnvironmentResource::class)->environment($environment);
            $attachment->refresh();
            $this->mark($attachment);
            app(MarkEnvironmentForRedeploy::class)->mark($environment);
            SyncEnvironmentResourceAccessJob::dispatch($attachment->id, $attachment->server_id)->afterCommit();
        });
    }

    public function mark(EnvironmentAttachment $attachment): void
    {
        $attachment->status = EnvironmentAttachmentStatus::DELETING;
        $attachment->last_error = null;
        $attachment->save();
    }
}
