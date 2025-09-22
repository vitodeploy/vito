<?php

namespace App\Actions\CronJob;

use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\Site;
use App\ValidationRules\CronRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EditCronJob
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function edit(Server $server, CronJob $cronJob, array $input, ?Site $site = null): CronJob
    {
        $this->validate($input, $server, $site);

        // Sync before editing to preserve any manual cronjobs
        app(SyncCronJobs::class)->sync($server);

        // Determine site_id: use provided site or from input
        $siteId = $site?->id;
        if (! $site && isset($input['site_id'])) {
            $siteId = ! empty($input['site_id']) ? (int) $input['site_id'] : null;
        }

        // Check if user has changed
        $originalUser = $cronJob->user;
        $newUser = $input['user'];
        $userChanged = $originalUser !== $newUser;

        $cronJob->update([
            'site_id' => $siteId,
            'user' => $input['user'],
            'command' => $input['command'],
            'frequency' => $input['frequency'] == 'custom' ? $input['custom'] : $input['frequency'],
            'status' => CronjobStatus::UPDATING,
        ]);
        $cronJob->save();

        // If user changed, remove from original user's crontab first
        if ($userChanged) {
            $server->cron()->update($originalUser, CronJob::crontab($server, $originalUser));
        }

        // Update the new user's crontab
        $server->cron()->update($cronJob->user, CronJob::crontab($server, $cronJob->user));
        $cronJob->status = CronjobStatus::READY;
        $cronJob->save();

        return $cronJob;
    }

    private function validate(array $input, Server $server, ?Site $site = null): void
    {
        $rules = [
            'command' => [
                'required',
            ],
            'user' => [
                'required',
                Rule::in($site?->getSshUsers() ?? $server->getSshUsers()),
            ],
            'frequency' => [
                'required',
                new CronRule(acceptCustom: true),
            ],
        ];

        // Add site_id validation if provided in input
        if (isset($input['site_id']) && ! empty($input['site_id'])) {
            $rules['site_id'] = [
                'required',
                'integer',
                'exists:sites,id',
                Rule::exists('sites', 'id')->where('server_id', $server->id),
            ];
        }

        if (isset($input['frequency']) && $input['frequency'] == 'custom') {
            $rules['custom'] = [
                'required',
                new CronRule,
            ];
        }

        Validator::make($input, $rules)->validate();
    }
}
