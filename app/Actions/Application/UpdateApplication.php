<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Jobs\Component\SyncComponentDeployKeysJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateApplication
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Application $application, array $input, User $actor): Application
    {
        app(ValidateApplication::class)->validate($application->project, $input, $actor, $application);

        return DB::transaction(function () use ($application, $input): Application {
            $application = Application::query()->lockForUpdate()->findOrFail($application->id);
            if ($application->status !== ApplicationStatus::READY) {
                throw ValidationException::withMessages(['name' => 'The application cannot be edited during teardown.']);
            }

            $changed = $application->source_control_id !== (int) $input['source_control_id'] || $application->repository !== $input['repository'];
            if ($changed) {
                foreach ($application->environments()->orderBy('id')->lockForUpdate()->get() as $environment) {
                    app(\App\Actions\EnvironmentDeployment\AssertEnvironmentIdle::class)->validate($environment, 'repository');
                }
            }
            $application->update([
                'name' => $input['name'],
                'source_control_id' => $input['source_control_id'],
                'repository' => $input['repository'],
            ]);

            if ($changed) {
                Component::query()->whereHas('environment', fn ($query) => $query->where('application_id', $application->id))
                    ->where('status', ComponentStatus::READY)->each(function (Component $component): void {
                        $component->status = ComponentStatus::PENDING;
                        $component->needs_redeploy = true;
                        $component->save();
                        DB::afterCommit(fn () => app(\App\Actions\Component\BroadcastComponentUpdate::class)->broadcast($component));
                        SyncComponentDeployKeysJob::dispatch($component->id, $component->server_id)->afterCommit();
                    });
            }

            return $application;
        });
    }
}
