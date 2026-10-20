<?php

namespace App\Actions\CronJob;

use App\Actions\Component\ValidateComponentProcess;
use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\Site;
use App\ValidationRules\CronRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateCronJob
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function create(Server $server, array $input, ?Site $site = null, ?Component $component = null, ?ComponentCapability $managedBy = null): CronJob
    {
        return app(SyncCronJobs::class)->run($server, fn () => app(ValidateComponentProcess::class)->run($managedBy ? null : $component, function () use ($server, $input, $site, $component, $managedBy): CronJob {
            app(ValidateComponentProcess::class)->validate($server, $input, $component, 'scheduler', managedBy: $managedBy);
            if ($component) {
                $input['user'] = $component->user;
            }
            $this->validate($input, $server, $site, $component);
            if ($component) {
                app(SyncCronJobs::class)->sync($server, locked: true);
            }

            $siteId = $site?->id;
            if (! $site && isset($input['site_id']) && ! empty($input['site_id'])) {
                $siteId = (int) $input['site_id'];
            }

            $cronJob = new CronJob([
                'name' => $input['name'] ?? null,
                'server_id' => $server->id,
                'site_id' => $siteId,
                'user' => $input['user'],
                'command' => $input['command'],
                'frequency' => $input['frequency'] == 'custom' ? $input['custom'] : $input['frequency'],
                'status' => CronjobStatus::CREATING,
            ]);
            $cronJob->component_id = $component?->id;
            $cronJob->component_capability_id = $managedBy?->id;
            $cronJob->save();

            $server->cron()->update($cronJob->user, CronJob::crontab($server, $cronJob->user));
            $cronJob->status = CronjobStatus::READY;
            $cronJob->save();

            return $cronJob;
        }), background: $managedBy !== null);
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
