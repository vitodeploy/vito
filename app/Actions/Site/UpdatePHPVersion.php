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

class UpdatePHPVersion
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, [
            'version' => [
                'required',
                Rule::exists('services', 'version')
                    ->where('server_id', $site->server_id)
                    ->where('type', 'php'),
            ],
        ])->validate();

        $newVersion = $input['version'];
        $oldVersion = $site->php_version;

        if ($oldVersion === $newVersion) {
            return;
        }

        if ($site->isIsolated()) {
            $lock = $site->isolatedUser?->lock() ?? $site->server->isolatedUserLock($site->user);

            try {
                $lock->block(30);
            } catch (LockTimeoutException) {
                throw ValidationException::withMessages([
                    'version' => "Another operation on isolated user '{$site->user}' is in progress, please retry.",
                ]);
            }

            try {
                /** @var Service $phpService */
                $phpService = $site->server->php();
                /** @var PHP $php */
                $php = $phpService->handler();

                $php->createFpmPool($site->user, $newVersion);

                try {
                    if (! $site->fpmPoolSharedWithSiblings($oldVersion)) {
                        $php->removeFpmPool($site->user, $oldVersion, $site->id);
                    }

                    $site->php_version = $newVersion;
                    $site->save();

                    $site->webserver()->updateVHost($site);
                } catch (Throwable $e) {
                    Log::error('PHP version switch left orphan FPM pool', [
                        'site_id' => $site->id,
                        'server_id' => $site->server_id,
                        'user' => $site->user,
                        'old_version' => $oldVersion,
                        'new_version' => $newVersion,
                        'exception' => $e->getMessage(),
                    ]);

                    throw $e;
                }
            } finally {
                $lock->release();
            }

            return;
        }

        $site->php_version = $newVersion;
        $site->save();

        $site->webserver()->updateVHost($site);
    }
}
