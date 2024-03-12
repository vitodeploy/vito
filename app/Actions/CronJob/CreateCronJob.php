<?php

namespace App\Actions\CronJob;

use App\Enums\CronjobStatus;
use App\Models\CronJob;
use App\Models\Server;
use App\ValidationRules\CronRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateCronJob
{
    public function create(Server $server, array $input): void
    {
        $this->validate($input);

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

    /**
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
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
        ])->validate();

        if ($input['frequency'] == 'custom') {
            Validator::make($input, [
                'custom' => [
                    'required',
                    new CronRule(),
                ],
            ])->validate();
        }
    }
}
