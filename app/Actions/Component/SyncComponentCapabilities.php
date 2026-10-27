<?php

namespace App\Actions\Component;

use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Models\Component;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Throwable;

class SyncComponentCapabilities
{
    public function handle(int $componentId): void
    {
        $component = Component::query()->with('environment.application')->find($componentId);
        if (! $component || ! in_array($component->status, [ComponentStatus::PENDING, ComponentStatus::INSTALLING], true)
            || $component->environment->status !== EnvironmentStatus::READY
            || $component->environment->application->status !== ApplicationStatus::READY) {
            return;
        }

        $this->sync($component);
        $component->refresh();
        if (in_array($component->status, [ComponentStatus::PENDING, ComponentStatus::INSTALLING], true)) {
            $component->status = ComponentStatus::READY;
            $component->last_error = null;
            $component->save();
            app(BroadcastComponentUpdate::class)->broadcast($component);
        }
    }

    public function sync(Component $component): void
    {
        foreach ($component->capabilities()->orderBy('id')->get() as $capability) {
            $removing = in_array($capability->status, [ComponentStatus::DELETING, ComponentStatus::DELETION_FAILED], true);
            if ($capability->status === ComponentStatus::READY) {
                continue;
            }
            try {
                if ($removing) {
                    $capability->handler()->uninstall($component);
                    $capability->delete();
                } else {
                    $capability->status = ComponentStatus::INSTALLING;
                    $capability->save();
                    $capability->handler()->install($component);
                    $capability->status = ComponentStatus::READY;
                    $capability->save();
                }
            } catch (Throwable $e) {
                if ($e instanceof LockTimeoutException) {
                    throw $e;
                }
                $capability->status = $removing ? ComponentStatus::DELETION_FAILED : ComponentStatus::INSTALLATION_FAILED;
                $capability->save();
                throw $e;
            }
        }
    }
}
