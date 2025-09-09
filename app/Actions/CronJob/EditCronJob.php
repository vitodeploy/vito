<?php

namespace App\Actions\CronJob;

use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\CronJob;
use App\Models\Server;
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
    public function edit(Server $server, CronJob $cronJob, array $input): CronJob
    {
        $this->validate($input, $server);

        $cronJob->update([
            'user' => $input['user'],
            'command' => $input['command'],
            'frequency' => $input['frequency'] == 'custom' ? $input['custom'] : $input['frequency'],
            'status' => CronjobStatus::UPDATING,
        ]);
        $cronJob->save();

        $server->cron()->update($cronJob->user, CronJob::crontab($server, $cronJob->user));
        $cronJob->status = CronjobStatus::READY;
        $cronJob->save();

        return $cronJob;
    }

    private function validate(array $input, Server $server): void
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

        Validator::make($input, $rules)->validate();
    }
}
