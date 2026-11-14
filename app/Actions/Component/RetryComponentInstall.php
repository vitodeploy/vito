<?php

namespace App\Actions\Component;

use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Jobs\Component\InstallComponentJob;
use App\Models\Application;
use App\Models\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RetryComponentInstall
{
    public function retry(Component $component): void
    {
        DB::transaction(function () use ($component): void {
            Application::query()->lockForUpdate()->findOrFail($component->environment->application_id);
            $component->refresh();
            if ($component->status !== ComponentStatus::INSTALLATION_FAILED
                || $component->environment->status !== EnvironmentStatus::READY
                || $component->environment->application->status !== ApplicationStatus::READY) {
                throw ValidationException::withMessages(['component' => 'Only a failed installation outside teardown can be retried.']);
            }

            $component->status = ComponentStatus::PENDING;
            $component->last_error = null;
            $component->save();
            DB::afterCommit(fn () => app(BroadcastComponentUpdate::class)->broadcast($component));
            InstallComponentJob::dispatch($component->id, $component->server_id)->afterCommit();
        });
    }
}
