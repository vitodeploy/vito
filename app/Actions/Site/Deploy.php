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
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

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
            try {
                $site->sourceControl->getRepo($site->repository);
            } catch (HttpClientException $e) {
                Log::warning('Source control API unavailable; deploying without the repository check.', [
                    'site_id' => $site->id,
                    'exception' => $e::class,
                ]);
            }
        }

        if (! $site->deploymentScript?->content) {
            throw new DeploymentScriptIsEmptyException;
        }

        $lastCommit = $this->resolveCommit($site, $commitHash);

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

    /**
     * @return array{commit_id: string, commit_data: array<string, mixed>}|null
     *
     * @throws ValidationException
     */
    private function resolveCommit(Site $site, ?string $commitHash): ?array
    {
        $provider = $site->sourceControl?->provider();

        if ($commitHash === null) {
            try {
                return $provider?->getLastCommit($site->repository, $site->branch);
            } catch (Throwable $e) {
                Log::warning('Failed to fetch the last commit; deploying without commit details.', [
                    'site_id' => $site->id,
                    'exception' => $e::class,
                ]);

                return null;
            }
        }

        try {
            $commit = $provider?->getLastCommit($site->repository, $commitHash);
        } catch (RepositoryNotFound) {
            $commit = null;
        } catch (RequestException $e) {
            if ($e->response->status() !== 422) {
                throw $e;
            }
            $commit = null;
        }

        if (strtolower($commit['commit_id'] ?? '') !== $commitHash) {
            throw ValidationException::withMessages([
                'commit_hash' => 'The requested commit was not found in the site repository.',
            ]);
        }

        return $commit;
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
