<?php

namespace App\Actions\Site;

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

        DB::transaction(function () use ($site, $newSourceControl, $oldDeployKeyId): void {
            $site->source_control_id = $newSourceControl->id;
            $site->setRelation('sourceControl', $newSourceControl);
            if ($oldDeployKeyId) {
                $site->jsonUpdate('type_data', 'deploy_key_id', null, save: false);
            }
            $site->save();
        });

        if ($oldDeployKeyId && $oldSourceControl) {
            try {
                $oldSourceControl->provider()->deleteDeployKey((string) $oldDeployKeyId, $site->repository);
            } catch (Throwable $e) {
                Log::warning('Failed to delete old deploy key on source-control swap', [
                    'site_id' => $site->id,
                    'error' => $e->getMessage(),
                ]);
            }
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

        $newRepoUrl = $newSourceControl->provider()->fullRepoUrl(
            $site->repository,
            $site->getSshKeyName()
        );

        try {
            app(Git::class)->setRemote($site, $newRepoUrl);
        } catch (SSHError $e) {
            Log::warning('Failed to rewrite remote URL after source-control swap', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
