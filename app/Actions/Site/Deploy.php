<?php

namespace App\Actions\Site;

use App\Enums\DeploymentStatus;
use App\Exceptions\DeploymentScriptIsEmptyException;
use App\Exceptions\SourceControlIsNotConnected;
use App\Models\Deployment;
use App\Models\Site;

class Deploy
{
    /**
     * @throws SourceControlIsNotConnected
     * @throws DeploymentScriptIsEmptyException
     */
    public function run(Site $site): Deployment
    {
        if ($site->sourceControl()) {
            $site->sourceControl()->getRepo($site->repository);
        }

        if (! $site->deploymentScript?->content) {
            throw new DeploymentScriptIsEmptyException();
        }

        $deployment = new Deployment([
            'site_id' => $site->id,
            'deployment_script_id' => $site->deploymentScript->id,
            'status' => DeploymentStatus::DEPLOYING,
        ]);
        $lastCommit = $site->sourceControl()->provider()->getLastCommit($site->repository, $site->branch);
        if ($lastCommit) {
            $deployment->commit_id = $lastCommit['commit_id'];
            $deployment->commit_data = $lastCommit['commit_data'];
        }
        $deployment->save();

        dispatch(function () use ($site, $deployment) {
            $log = $site->server->os()->runScript($site->path, $site->deploymentScript->content, $site->id);
            $deployment->status = DeploymentStatus::FINISHED;
            $deployment->log_id = $log->id;
            $deployment->save();
        })->catch(function () use ($deployment) {
            $deployment->status = DeploymentStatus::FAILED;
            $deployment->save();
        })->onConnection('ssh');

        return $deployment;
    }
}
