<?php

namespace App\Actions\CronJob;

use App\Enums\CronjobStatus;
use App\Models\CronJob;
use App\Models\Server;
use App\ValidationRules\CronRule;

class CreateCronJob
{
    public function create(Server $server, array $input): void
    {
        $cronJob = new CronJob([
            'server_id' => $server->id,
            'user' => $input['user'],
            'command' => $input['command'],
            'frequency' => $input['frequency'] == 'custom' ? $input['custom'] : $input['frequency'],
            'status' => CronjobStatus::CREATING,
        ]);
        $cronJob->save();

        $server->cron()->update($cronJob->user, CronJob::crontab($server, $cronJob->user));
        $cronJob->status = CronjobStatus::READY;
        $cronJob->save();
    }

    public static function rules(array $input): array
    {
        $rules = [
            'command' => [
                'required',
            ],
            'user' => [
                'required',
                'in:root,'.config('core.ssh_user'),
            ],
            'frequency' => [
                'required',
                new CronRule(acceptCustom: true),
            ],
        ];

        if (isset($input['frequency']) && $input['frequency'] == 'custom') {
            $rules['custom'] = [
                'required',
                new CronRule,
            ];
        }

        return $rules;
    }
}
