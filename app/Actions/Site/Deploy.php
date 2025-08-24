<?php

namespace App\Actions\Site;

use App\Enums\DeploymentStatus;
use App\Exceptions\DeploymentScriptIsEmptyException;
use App\Facades\Notifier;
use App\Models\Deployment;
use App\Models\ServerLog;
use App\Models\Site;
use App\Notifications\DeploymentCompleted;
use App\Services\ProcessManager\ProcessManager;
use App\SSH\OS\Git;

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
        dispatch(function () use ($site, $deployment, $log): void {
            $site->server->os()->runScript(
                path: $site->path,
                script: $site->deploymentScript->content,
                serverLog: $log,
                user: $site->user,
                variables: $site->environmentVariables($deployment),
            );

            if ($site->deploymentScript->shouldRestartWorkers()) {
                /** @var ProcessManager $processManager */
                $processManager = $site->server->processManager()->handler();
                $processManager->restartAll($site->id);
            }

            $deployment->status = DeploymentStatus::FINISHED;
            $deployment->save();
            $deployment->activate();
            Notifier::send($site, new DeploymentCompleted($deployment, $site));
        })->catch(function () use ($deployment, $site): void {
            $deployment->status = DeploymentStatus::FAILED;
            $deployment->save();
            $deployment->activate();
            Notifier::send($site, new DeploymentCompleted($deployment, $site));
        })->onQueue('ssh-unique');

        return $deployment;
    }

    private function deployModern(Site $site, Deployment $deployment, ServerLog $log): Deployment
    {
        $deployment->release = now()->format('YmdHis');
        $deployment->save();
        /** @var ?Deployment $current */
        $current = $site->deployments()->where('active', 1)->whereNotNull('release')->first();

        dispatch(function () use ($site, $deployment, $log): void {
            app(Git::class)->clone($site, $deployment->path());

            // build
            $site->server->os()->runScript(
                path: $deployment->path(),
                script: $site->buildScript->content ?? '',
                serverLog: $log,
                user: $site->user,
                variables: $site->environmentVariables($deployment),
            );

            // link resources
            $site->server->ssh($site->user)->exec(
                view('ssh.modern-deployment.link-resources', [
                    'site' => $site,
                    'releasePath' => $deployment->path(),
                ]),
                'link-resources',
                $site->id
            );

            // pre-flight
            $site->server->os()->runScript(
                path: $deployment->path(),
                script: $site->preFlightScript->content ?? '',
                serverLog: $log,
                user: $site->user,
                variables: $site->environmentVariables($deployment),
            );

            // release
            $site->server->ssh($site->user)->exec(
                view('ssh.modern-deployment.release', [
                    'site' => $site,
                    'releasePath' => $deployment->path(),
                ]),
                'release',
                $site->id
            );

            if ($site->preFlightScript?->shouldRestartWorkers()) {
                /** @var ProcessManager $processManager */
                $processManager = $site->server->processManager()->handler();
                $processManager->restartAll($site->id);
            }

            $deployment->status = DeploymentStatus::FINISHED;
            $deployment->save();
            $deployment->activate();
            Notifier::send($site, new DeploymentCompleted($deployment, $site));
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

        return $deployment;
    }
}
