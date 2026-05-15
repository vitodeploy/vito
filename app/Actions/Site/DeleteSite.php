<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Service;
use App\Models\Site;
use App\Services\PHP\PHP;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeleteSite
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function delete(Site $site, array $input): void
    {
        $this->validate($site, $input);

        if ($site->sourceControl && isset($site->type_data['deploy_key_id'])) {
            $site->sourceControl->provider()->deleteDeployKey(
                $site->type_data['deploy_key_id'],
                $site->repository,
            );
        }

        if (! $site->isIsolated()) {
            $site->webserver()->deleteSite($site);
            $this->deleteRow($site);

            return;
        }

        $lock = $site->server->isolatedUserLock($site->user);

        try {
            $lock->block(30);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'domain' => "Another operation on isolated user '{$site->user}' is in progress, please retry.",
            ]);
        }

        try {
            $site->webserver()->deleteSite($site);

            if ($site->type()->language() === 'php' && ! $site->fpmPoolSharedWithSiblings()) {
                /** @var Service $phpService */
                $phpService = $site->server->php();
                /** @var PHP $php */
                $php = $phpService->handler();
                $php->removeFpmPool($site->user, $site->php_version, $site->id);
            }

            if (! $site->userSharedWithSiblings()) {
                $site->server->os()->deleteIsolatedUser($site->user);
            }

            $this->deleteRow($site);
        } finally {
            $lock->release();
        }
    }

    /**
     * Delete the site row. If the row deletion fails after server-side teardown has
     * already run, the server-side artefacts (pool, user) are already gone — log the
     * orphan-row state so an operator can clean up, then rethrow.
     */
    private function deleteRow(Site $site): void
    {
        try {
            $site->delete();
        } catch (Throwable $e) {
            Log::error('Site row deletion failed after isolated teardown', [
                'site_id' => $site->id,
                'server_id' => $site->server_id,
                'user' => $site->user,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Site $site, array $input): void
    {
        Validator::make($input, [
            'domain' => [
                'required',
                Rule::in($site->domain),
            ],
        ])->validate();
    }
}
