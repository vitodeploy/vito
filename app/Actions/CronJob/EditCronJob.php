<?php

namespace App\Actions\CronJob;

use App\Actions\Component\ValidateComponentProcess;
use App\Models\Component;
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
    public function edit(Server $server, CronJob $cronJob, array $input, ?Site $site = null, ?Component $component = null): CronJob
    {
        $component ??= $cronJob->component;
        return app(SyncCronJobs::class)->run($server, fn () => app(ValidateComponentProcess::class)->run($component, function () use ($server, $cronJob, $input, $site, $component): CronJob {
            $cronJob->refresh();
            app(ValidateComponentProcess::class)->validate($server, $input, $component, 'scheduler', $cronJob);
            if ($component) {
                $input['user'] = $component->user;
            }
            $this->validate($input, $server, $site, $component);

            app(SyncCronJobs::class)->sync($server, locked: true);

            $siteId = $site?->id;
            if (! $site && isset($input['site_id'])) {
                $siteId = ! empty($input['site_id']) ? (int) $input['site_id'] : null;
            }

            $originalUser = $cronJob->user;
            $newUser = $input['user'];
            $userChanged = $originalUser !== $newUser;

            $cronJob->update([
                'site_id' => $siteId,
                'name' => $input['name'] ?? null,
                'user' => $input['user'],
                'command' => $input['command'],
                'frequency' => $input['frequency'] == 'custom' ? $input['custom'] : $input['frequency'],
                'status' => CronjobStatus::UPDATING,
            ]);
            $cronJob->save();

            if ($userChanged) {
                $server->cron()->update($originalUser, CronJob::crontab($server, $originalUser));
            }

            $server->cron()->update($cronJob->user, CronJob::crontab($server, $cronJob->user));
            $cronJob->status = CronjobStatus::READY;
            $cronJob->save();

            return $cronJob;
        }));
    }

    private function validate(array $input, Server $server, ?Site $site = null, ?Component $component = null): void
    {
        $rules = [
            'command' => [
                'required', 'string', 'max:65535', 'not_regex:/\x00/',
            ],
            'user' => [
                'required',
                Rule::in($component ? [$component->user] : ($site?->getSshUsers() ?? $server->getSshUsers())),
            ],
            'frequency' => [
                'required',
                new CronRule(acceptCustom: true),
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];

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
