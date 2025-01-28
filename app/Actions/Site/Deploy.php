<?php

namespace App\Actions\Site;

use App\Enums\DeploymentStatus;
use App\Exceptions\DeploymentScriptIsEmptyException;
use App\Exceptions\SSHError;
use App\Facades\Notifier;
use App\Models\Deployment;
use App\Models\ServerLog;
use App\Models\Site;
use App\Notifications\DeploymentCompleted;

class Deploy
{
    /**
     * @throws DeploymentScriptIsEmptyException
     * @throws SSHError
     */
    public function run(Site $site): Deployment
    {
        if ($site->sourceControl) {
            $site->sourceControl->getRepo($site->repository);
        }

        if (! $site->deploymentScript?->content) {
            throw new DeploymentScriptIsEmptyException;
        }

        $deployment = new Deployment([
            'site_id' => $site->id,
            'deployment_script_id' => $site->deploymentScript->id,
            'status' => DeploymentStatus::DEPLOYING,
        ]);
        $lastCommit = $site->sourceControl?->provider()?->getLastCommit($site->repository, $site->branch);
        if ($lastCommit) {
            $deployment->commit_id = $lastCommit['commit_id'];
            $deployment->commit_data = $lastCommit['commit_data'];
        }
        $deployment->save();

        dispatch(function () use ($site, $deployment) {
            /** @var ServerLog $log */
            $log = ServerLog::make($site->server, 'deploy-'.strtotime('now'))
                ->forSite($site);
            $log->save();
            $deployment->log_id = $log->id;
            $deployment->save();
            $site->server->os()->runScript(
                path: $site->path,
                script: $site->deploymentScript->content,
                serverLog: $log,
                user: $site->user,
                variables: $site->environmentVariables($deployment),
            );
            $deployment->status = DeploymentStatus::FINISHED;
            $deployment->save();
            Notifier::send($site, new DeploymentCompleted($deployment, $site));
        })->catch(function () use ($deployment, $site) {
            $deployment->status = DeploymentStatus::FAILED;
            $deployment->save();
            Notifier::send($site, new DeploymentCompleted($deployment, $site));
        })->onConnection('ssh');

        return $deployment;
    }
}
