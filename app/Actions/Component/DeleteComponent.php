<?php

namespace App\Actions\Component;

use App\Actions\CronJob\DeleteCronJob;
use App\Actions\Worker\DeleteWorker;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Jobs\Component\DeleteComponentJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeleteComponent
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function delete(Component $component, array $input): void
    {
        DB::transaction(function () use ($component, $input): void {
            Application::query()->lockForUpdate()->findOrFail($component->environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($component->environment_id);
            $component->refresh();
            Validator::make($input, [
                'primary_component_id' => ['nullable', 'integer', Rule::exists('components', 'id')
                    ->where('environment_id', $environment->id)->where('type', ComponentType::APP->value)
                    ->whereNot('id', $component->id)->whereNotIn('status', [ComponentStatus::DELETING->value, ComponentStatus::DELETION_FAILED->value])],
            ])->validate();

            if ($component->status === ComponentStatus::DELETING) {
                throw ValidationException::withMessages(['component' => 'Component deletion is already in progress.']);
            }

            if ($environment->primary_component_id === $component->id) {
                $environment->primary_component_id = $input['primary_component_id'] ?? null;
                $environment->save();
            }

            $component->status = ComponentStatus::DELETING;
            $component->last_error = null;
            $component->save();
            DeleteComponentJob::dispatch($component->id, $component->server_id)->afterCommit();
        });
    }

    public function handle(int $componentId): void
    {
        $component = Component::query()->with('server', 'isolatedUser')->find($componentId);
        if (! $component || $component->status !== ComponentStatus::DELETING) {
            return;
        }

        app(BroadcastComponentUpdate::class)->broadcast($component);
        foreach ($component->capabilities()->get() as $capability) {
            $capability->handler()->uninstall($component);
        }
        app(DeleteWorker::class)->deleteForComponent($component);
        foreach ($component->cronJobs()->get() as $cronJob) {
            app(DeleteCronJob::class)->remove($component->server, $cronJob);
        }
        $keys = app(SyncComponentDeployKeys::class);
        $keys->remove($component);
        $keys->removeLocal($component);
        $component->server->ssh()->exec(view('ssh.components.delete-directories', ['component' => $component]), 'component-delete-directories');

        $isolatedUser = $component->isolatedUser;
        if ($isolatedUser) {
            $isolatedUser->lock()->block(30, function () use ($isolatedUser, $component): void {
                if (! $isolatedUser->isUsedByOthers($component)) {
                    $component->server->os()->deleteIsolatedUser($component->user);
                    $isolatedUser->delete();
                }
                $component->delete();
            });
        } else {
            $component->delete();
        }

        app(BroadcastComponentUpdate::class)->deleted($component);
    }

    public function failed(int $componentId): void
    {
        $component = Component::query()->find($componentId);
        if (! $component) {
            return;
        }

        $component->status = ComponentStatus::DELETION_FAILED;
        $component->last_error = 'Component deletion failed. Check server connectivity and source-control access, then retry deletion.';
        $component->save();
        app(BroadcastComponentUpdate::class)->broadcast($component);
    }
}
