<?php

namespace App\Actions\CronJob;

use App\Enums\CronjobStatus;
use App\Models\CronJob;
use App\Models\Server;
use App\ValidationRules\CronRule;
use Illuminate\Validation\Rule;

class CreateCronJob
{
    public function create(Server $server, array $input): CronJob
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

        return $cronJob;
    }

    public static function rules(array $input, Server $server): array
    {
        $rules = [
            'command' => [
                'required',
            ],
            'user' => [
                'required',
                Rule::in($server->getSshUsers()),
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
