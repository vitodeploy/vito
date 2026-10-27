<?php

namespace App\Actions\Site;

use App\Actions\SourceControl\SetupDeployKey;
use App\Models\Site;
use App\Models\SourceControl;
use App\SourceControlProviders\Github;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class SyncSiteDeployKey
{
    public function setup(Site $site): void
    {
        $sourceControl = $site->sourceControl;
        if (! $sourceControl || $sourceControl->isGithubApp()) {
            return;
        }

        $repository = $site->repository;
        $this->run($sourceControl, $repository, function () use ($site, $sourceControl, $repository): void {
            $site->refresh();
            if ($site->source_control_id !== $sourceControl->id || $site->repository !== $repository) {
                throw ValidationException::withMessages(['source_control' => 'The site repository changed during key registration. Please retry.']);
            }
            if (! empty($site->type_data['deploy_key_id'])) {
                return;
            }

            $key = app(SetupDeployKey::class)->setup(
                $site->server,
                $site->user ?? $site->server->getSshUser(),
                $site->getSshKeyName(),
                $sourceControl,
                $repository,
                $site->getDeployKeyName(),
                publicKey: $site->ssh_key,
                siteId: $site->id,
            );
            $site->jsonUpdate('type_data', 'deploy_key_id', $key['deploy_key_id']);
        });
    }

    /**
     * Separate credentials can refer to the same repository key, including across projects.
     */
    public function remove(SourceControl $sourceControl, string $repository, string $keyId): void
    {
        $this->run($sourceControl, $repository, function () use ($sourceControl, $repository, $keyId): void {
            $identity = $this->repositoryIdentity($sourceControl, $repository);
            $shared = Site::query()->with('sourceControl')
                ->where('type_data->deploy_key_id', $keyId)->get()->contains(function (Site $site) use ($identity): bool {
                    $source = $site->sourceControl;
                    if (! $source || ! config('source-control.providers.'.$source->provider.'.handler')) {
                        return true;
                    }

                    return $this->repositoryIdentity($source, $site->repository) === $identity;
                });

            if (! $shared) {
                $sourceControl->provider()->deleteDeployKey($keyId, $repository);
            }
        });
    }

    /** @param Closure(): void $callback */
    private function run(SourceControl $sourceControl, string $repository, Closure $callback): void
    {
        $identity = $this->repositoryIdentity($sourceControl, $repository);
        try {
            Cache::lock('site-deploy-key:'.hash('sha256', $identity), 600)->block(30, $callback);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['source_control' => 'Another deploy-key operation on this repository is in progress. Please retry.']);
        }
    }

    private function repositoryIdentity(SourceControl $sourceControl, string $repository): string
    {
        $provider = $sourceControl->provider();
        $url = $provider->fullRepoUrl($repository, '');

        return $provider instanceof Github ? strtolower($url) : $url;
    }
}
