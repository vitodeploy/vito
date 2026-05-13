<?php

namespace App\Actions\Site;

use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Models\Site;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateSourceControl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, [
            'source_control' => [
                'required',
                Rule::exists('source_controls', 'id'),
            ],
        ])->validate();

        try {
            if ($site->sourceControl && isset($site->type_data['deploy_key_id'])) {
                $site->sourceControl->provider()->deleteDeployKey(
                    $site->type_data['deploy_key_id'],
                    $site->repository,
                );
            }
        } catch (Throwable $e) {
            Log::warning('Failed to delete previous deploy key during source control update', [
                'site' => $site->id,
                'error' => $e->getMessage(),
            ]);
        }

        $site->source_control_id = $input['source_control'];
        $site->unsetRelation('sourceControl');

        try {
            if ($site->sourceControl) {
                $site->sourceControl->getRepo($site->repository);
            }
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

        $site->save();

        if ($site->ssh_key && $site->repository) {
            try {
                $keyId = $site->sourceControl->provider()->deployKey(
                    $site->getDeployKeyName(),
                    $site->repository,
                    $site->ssh_key
                );
                $site->jsonUpdate('type_data', 'deploy_key_id', $keyId);
            } catch (Throwable $e) {
                Log::warning('Failed to re-deploy SSH key after source control update', [
                    'site' => $site->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
