<?php

namespace App\Actions\Site;

use App\Enums\DeploymentStatus;
use App\Exceptions\DeploymentScriptIsEmptyException;
use App\Jobs\Site\DeployJob;
use App\Models\Deployment;
use App\Models\ServerLog;
use App\Models\Site;

class Deploy
{
    /**
     * @throws DeploymentScriptIsEmptyException
     */
    public function run(Site $site, bool $modern = true): Deployment
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
        $log = ServerLog::newLog($site->server, 'deploy-'.strtotime('now'))
            ->forSite($site);
        $log->save();
        $deployment->log_id = $log->id;
        $deployment->save();
        $lastCommit = $site->sourceControl?->provider()?->getLastCommit($site->repository, $site->branch);
        if ($lastCommit) {
            $deployment->commit_id = $lastCommit['commit_id'];
            $deployment->commit_data = $lastCommit['commit_data'];
        }
        $deployment->save();

        $typeData = $site->type_data;

        if (! $modern || ! isset($typeData['modern_deployment']) || ! $typeData['modern_deployment']) {
            return $this->deployClassic($site, $deployment, $log);
        }

        return $this->deployModern($site, $deployment, $log);
    }

    private function deployClassic(Site $site, Deployment $deployment, ServerLog $log): Deployment
    {
        dispatch(new DeployJob($deployment, false))->onQueue('ssh');

        return $deployment;
    }

    private function deployModern(Site $site, Deployment $deployment, ServerLog $log): Deployment
    {
        $deployment->release = now()->format('YmdHis');
        $deployment->save();

        dispatch(new DeployJob($deployment, true))->onQueue('ssh');

        return $deployment;
    }
}
