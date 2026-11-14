<?php

namespace App\Actions\Site;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\User;
use App\SSH\OS\Git;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateSourceControl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws SSHError
     * @throws Throwable
     */
    public function update(Site $site, array $input, User $actor): void
    {
        Validator::make($input, [
            'source_control' => SourceControl::siteValidationRules($site->server),
        ])->validate();

        $newSourceControlId = (int) $input['source_control'];

        $newSourceControl = SourceControl::find($newSourceControlId);
        if (! $newSourceControl instanceof SourceControl) {
            throw ValidationException::withMessages([
                'source_control' => 'Source control not found',
            ]);
        }

        Gate::forUser($actor)->authorize('view', $newSourceControl);

        if ($site->source_control_id === $newSourceControlId) {
            return;
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

        $this->swap($site, $newSourceControl, null);

        try {
            app(SyncSiteDeployKey::class)->setup($site);
        } catch (Throwable $e) {
            $this->rollback($site, $newSourceControl, $oldSourceControl, $oldDeployKeyId);

            if ($e instanceof FailedToDeployGitKey) {
                throw ValidationException::withMessages([
                    'source_control' => 'Failed to add the deploy key to the new source control',
                ]);
            }

            throw $e;
        }

        try {
            app(Git::class)->setRemote($site, $newSourceControl->provider()->fullRepoUrl($site->repository, $site->getSshKeyName()));
        } catch (Throwable $e) {
            $newDeployKeyId = $site->type_data['deploy_key_id'] ?? null;
            if ($this->rollback($site, $newSourceControl, $oldSourceControl, $oldDeployKeyId) && $newDeployKeyId) {
                $this->removeDeployKey($site, $newSourceControl, (string) $newDeployKeyId);
            }

            throw $e;
        }

        if ($oldGitHook) {
            try {
                $oldGitHook->destroyHook();
                $site->unsetRelation('gitHook');
                $site->enableAutoDeployment();
            } catch (Throwable $e) {
                Log::warning('Failed to move the auto-deployment hook on source-control swap', [
                    'site_id' => $site->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($oldDeployKeyId && $oldSourceControl) {
            $this->removeDeployKey($site, $oldSourceControl, (string) $oldDeployKeyId);
        }
    }

    /**
     * Restores the previous source control unless another request has changed it meanwhile.
     */
    private function rollback(Site $site, SourceControl $newSourceControl, ?SourceControl $oldSourceControl, int|string|null $oldDeployKeyId): bool
    {
        $site->refresh();
        if ($site->source_control_id !== $newSourceControl->id) {
            return false;
        }

        $this->swap($site, $oldSourceControl, $oldDeployKeyId);

        return true;
    }

    private function removeDeployKey(Site $site, SourceControl $sourceControl, string $keyId): void
    {
        try {
            app(SyncSiteDeployKey::class)->remove($sourceControl, $site->repository, $keyId);
        } catch (Throwable $e) {
            Log::warning('Failed to delete deploy key on source-control swap', [
                'site_id' => $site->id,
                'deploy_key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function swap(Site $site, ?SourceControl $sourceControl, int|string|null $deployKeyId): void
    {
        $site->source_control_id = $sourceControl?->id;
        $site->setRelation('sourceControl', $sourceControl);
        $site->jsonUpdate('type_data', 'deploy_key_id', $deployKeyId);
    }
}
