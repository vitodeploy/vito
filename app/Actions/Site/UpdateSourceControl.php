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
     * @throws SSHError
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
            if (! $reuseOldDeployKey && $newDeployKeyId !== null) {
                $this->deleteDeployKey($site, $newSourceControl, $newDeployKeyId, 'Failed to remove new deploy key after source control update failed');
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
            $this->deleteDeployKey($site, $oldSourceControl, (string) $oldDeployKeyId, 'Failed to remove old deploy key during source control update');
        }
    }

    /**
     * @throws ValidationException
     * @throws SSHError
     */
    private function registerDeployKey(Site $site, SourceControl $sourceControl): ?string
    {
        if (! $site->repository || $sourceControl->isGithubApp()) {
            return null;
        }

        $publicKey = app(EnsureSiteSshKey::class)->ensure($site);

        try {
            $keyId = $sourceControl->provider()->deployKey($site->getDeployKeyName(), $site->repository, $publicKey);
        } catch (FailedToDeployGitKey $e) {
            Log::warning('Failed to deploy SSH key after source control update', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
            $keyId = '';
        }

        if ($keyId === '') {
            throw ValidationException::withMessages([
                'source_control' => 'Failed to add the deploy key to the new source control',
            ]);
        }

        return $keyId;
    }

    private function deleteDeployKey(Site $site, SourceControl $sourceControl, string $keyId, string $failureMessage): void
    {
        try {
            $sourceControl->provider()->deleteDeployKey($keyId, $site->repository);
        } catch (Throwable $e) {
            Log::warning($failureMessage, [
                'site_id' => $site->id,
                'deploy_key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
