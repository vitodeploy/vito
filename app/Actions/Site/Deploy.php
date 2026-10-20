<?php

namespace App\Actions\Site;

use App\DTOs\SocketEventDTO;
use App\Enums\DeploymentStatus;
use App\Events\SocketEvent;
use App\Exceptions\DeploymentScriptIsEmptyException;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\ReverseProxyNotConfiguredException;
use App\Http\Resources\DeploymentResource;
use App\Jobs\Site\DeployJob;
use App\Models\Deployment;
use App\Models\ServerLog;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Deploy
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws DeploymentScriptIsEmptyException
     * @throws ReverseProxyNotConfiguredException
     * @throws ValidationException
     */
    public function run(Site $site, bool $modern = true, array $input = []): Deployment
    {
        Validator::make($input, [
            'commit_hash' => ['sometimes', 'required', 'string', 'regex:/\A[a-f0-9]{40}\z/i'],
        ])->validate();

        $commitHash = isset($input['commit_hash']) ? strtolower($input['commit_hash']) : null;
        if ($commitHash !== null && ! $site->sourceControl) {
            throw ValidationException::withMessages([
                'commit_hash' => 'A source control connection is required to deploy a specific commit.',
            ]);
        }

        $site->type()->assertReadyToDeploy();

        if ($site->sourceControl) {
            $site->sourceControl->getRepo($site->repository);
        }

        if (! $site->deploymentScript?->content) {
            throw new DeploymentScriptIsEmptyException;
        }

        try {
            $lastCommit = $site->sourceControl?->provider()?->getLastCommit($site->repository, $commitHash ?? $site->branch);
        } catch (RepositoryNotFound $e) {
            if ($commitHash === null) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'commit_hash' => 'The requested commit was not found in the site repository.',
            ]);
        }
        if ($commitHash !== null && strtolower($lastCommit['commit_id'] ?? '') !== $commitHash) {
            throw ValidationException::withMessages([
                'commit_hash' => 'The source control provider could not resolve the requested commit.',
            ]);
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
        if ($lastCommit) {
            $deployment->commit_id = $lastCommit['commit_id'];
            $deployment->commit_data = $lastCommit['commit_data'];
        }
        $deployment->save();

        $typeData = $site->type_data;

        if (! $modern || ! isset($typeData['modern_deployment']) || ! $typeData['modern_deployment']) {
            return $this->deployClassic($site, $deployment, $log);
        }

        return $this->deployModern($site, $deployment, $log, $commitHash !== null);
    }

    private function broadcastDeploymentCreated(Site $site, Deployment $deployment): void
    {
        $deployment->loadMissing('log');

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $site->server->project_id,
            type: 'deployment.created',
            data: new DeploymentResource($deployment),
        ));
    }

    private function deployClassic(Site $site, Deployment $deployment, ServerLog $log): Deployment
    {
        dispatch(new DeployJob($deployment, false));

        $this->broadcastDeploymentCreated($site, $deployment);

        return $deployment;
    }

    private function deployModern(Site $site, Deployment $deployment, ServerLog $log, bool $checkoutCommit): Deployment
    {
        $deployment->release = now()->format('YmdHis');
        $deployment->save();

        dispatch(new DeployJob($deployment, true, $checkoutCommit));

        $this->broadcastDeploymentCreated($site, $deployment);

        return $deployment;
    }
}
