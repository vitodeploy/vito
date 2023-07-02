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
            'frequency' => $input['frequency'],
            'status' => CronjobStatus::CREATING,
        ]);
        $cronJob->save();
        $cronJob->addToServer();
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
                new CronRule(),
            ],
        ])->validateWithBag('createCronJob');
    }
}
