<?php

namespace App\Actions\Site;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Models\SourceControl;
use App\SSH\OS\Git;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateSourceControl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, [
            'source_control' => SourceControl::siteValidationRules($site->server),
        ])->validate();

        $newSourceControlId = (int) $input['source_control'];

        if ($site->source_control_id === $newSourceControlId) {
            return;
        }

        $newSourceControl = SourceControl::find($newSourceControlId);
        if (! $newSourceControl instanceof SourceControl) {
            throw ValidationException::withMessages([
                'source_control' => 'Source control not found',
            ]);
        }

        try {
            $newSourceControl->getRepo($site->repository);
        } catch (SourceControlIsNotConnected) {
            throw ValidationException::withMessages([
                'source_control' => 'Source control is not connected',
            ]);
        } catch (RepositoryPermissionDenied) {
            throw ValidationException::withMessages([
                'repository' => 'You do not have permission to access this repository',
            ]);
        } catch (RepositoryNotFound) {
            throw ValidationException::withMessages([
                'repository' => 'Repository not found',
            ]);
        }

        $oldSourceControl = $site->sourceControl;
        $oldDeployKeyId = $site->type_data['deploy_key_id'] ?? null;
        $oldGitHook = $site->gitHook;
        $newRepoUrl = $newSourceControl->provider()->fullRepoUrl(
            $site->repository,
            $site->getSshKeyName()
        );
        $reuseOldDeployKey = $oldDeployKeyId !== null
            && $oldSourceControl
            && $oldSourceControl->provider()->fullRepoUrl(
                $site->repository,
                $site->getSshKeyName()
            ) === $newRepoUrl;

        if ($oldDeployKeyId !== null && ! $oldSourceControl) {
            Log::warning('Skipped old deploy key removal because its source control is unavailable', [
                'site_id' => $site->id,
                'deploy_key_id' => (string) $oldDeployKeyId,
            ]);
        }

        $newDeployKeyId = $reuseOldDeployKey
            ? (string) $oldDeployKeyId
            : $this->registerDeployKey($site, $newSourceControl);

        try {
            DB::transaction(function () use ($site, $newSourceControl, $oldDeployKeyId, $newDeployKeyId): void {
                $site->source_control_id = $newSourceControl->id;
                $site->setRelation('sourceControl', $newSourceControl);
                if ($oldDeployKeyId !== null || $newDeployKeyId !== null) {
                    $site->jsonUpdate('type_data', 'deploy_key_id', $newDeployKeyId, save: false);
                }
                $site->save();
            });
        } catch (Throwable $e) {
            if (! $reuseOldDeployKey) {
                $this->removeRegisteredDeployKey($site, $newSourceControl, $newDeployKeyId);
            }

            throw $e;
        }

        if ($oldGitHook) {
            try {
                $oldGitHook->destroyHook();
            } catch (Throwable $e) {
                Log::warning('Failed to destroy old git hook on source-control swap', [
                    'site_id' => $site->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $remoteUpdated = true;
        try {
            app(Git::class)->setRemote($site, $newRepoUrl);
        } catch (SSHError $e) {
            $remoteUpdated = false;
            Log::warning('Failed to rewrite remote URL after source-control swap', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($remoteUpdated && ! $reuseOldDeployKey && $oldDeployKeyId !== null && $oldSourceControl) {
            $this->removeOldDeployKey($site, $oldSourceControl, (string) $oldDeployKeyId);
        }
    }

    private function registerDeployKey(Site $site, SourceControl $sourceControl): ?string
    {
        if (! $site->ssh_key || ! $site->repository || $sourceControl->isGithubApp()) {
            return null;
        }

        try {
            $keyId = $sourceControl->provider()->deployKey(
                $site->getDeployKeyName(),
                $site->repository,
                $site->ssh_key,
            );
        } catch (Throwable $e) {
            Log::warning('Failed to re-deploy SSH key after source control update', [
                'site_id' => $site->id,
                'exception' => $e::class,
            ]);

            throw new FailedToDeployGitKey('Source control provider failed to deploy the key.');
        }

        if ($keyId === '') {
            throw new FailedToDeployGitKey('Source control provider did not return a deploy key ID.');
        }

        return $keyId;
    }

    private function removeOldDeployKey(Site $site, SourceControl $sourceControl, string $keyId): void
    {
        try {
            $sourceControl->provider()->deleteDeployKey($keyId, $site->repository);
        } catch (Throwable $e) {
            Log::warning('Failed to remove old deploy key during source control update', [
                'site_id' => $site->id,
                'deploy_key_id' => $keyId,
                'exception' => $e::class,
            ]);
        }
    }

    private function removeRegisteredDeployKey(Site $site, SourceControl $sourceControl, ?string $keyId): void
    {
        if ($keyId === null) {
            return;
        }

        try {
            $sourceControl->provider()->deleteDeployKey($keyId, $site->repository);
        } catch (Throwable $e) {
            Log::warning('Failed to remove new deploy key after source control update failed', [
                'site_id' => $site->id,
                'deploy_key_id' => $keyId,
                'exception' => $e::class,
            ]);
        }
    }
}
