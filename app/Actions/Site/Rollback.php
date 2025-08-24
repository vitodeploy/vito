<?php

namespace App\Actions\Site;

use App\Enums\DeploymentStatus;
use App\Facades\Notifier;
use App\Models\Deployment;
use App\Notifications\DeploymentCompleted;
use Illuminate\Validation\ValidationException;

class Rollback
{
    public function run(Deployment $deployment): void
    {
        $site = $deployment->site;
        /** ?Deployment $current */
        $current = $site->deployments()->where('active', 1)->whereNotNull('release')->first();

        if ($deployment->active) {
            throw ValidationException::withMessages([
                'deployment' => 'This release is already the active release!',
            ]);
        }

        if (! $deployment->release) {
            throw ValidationException::withMessages([
                'deployment' => 'Release not found!',
            ]);
        }

        $deployment->status = DeploymentStatus::DEPLOYING;
        $deployment->save();

        dispatch(function () use ($deployment, $site) {
            $deployment->site->server->ssh($site->user)->exec(
                view('ssh.modern-deployment.release', [
                    'site' => $site,
                    'releasePath' => $deployment->path(),
                ]),
                'release',
                $site->id
            );
            $deployment->activate();
            $deployment->status = DeploymentStatus::FINISHED;
            $deployment->save();
        })->catch(function () use ($deployment, $site, $current): void {
            $deployment->status = DeploymentStatus::FAILED;
            $deployment->save();
            Notifier::send($site, new DeploymentCompleted($deployment, $site));
            if ($current) {
                $deployment->site->server->ssh($site->user)->exec(
                    view('ssh.modern-deployment.release', [
                        'site' => $site,
                        'releasePath' => $current->path(),
                    ]),
                    'release',
                    $site->id
                );
                $current->activate();

            }
        })->onQueue('ssh-unique');
    }
}
