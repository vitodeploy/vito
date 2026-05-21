<?php

namespace App\Actions\Server;

use App\Models\Server;
use App\Services\ServiceLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClearServiceLog
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     * @throws ValidationException
     */
    public function run(Server $server, array $input): void
    {
        $data = Validator::make($input, [
            'key' => ['required', 'string', 'max:200'],
        ])->validate();

        $log = app(GetServiceLogs::class)->resolve($server, $data['key']);
        abort_if($log === null, 404);
        abort_if($log->source !== ServiceLog::SOURCE_FILE, 422, 'Journal logs cannot be cleared.');

        $server->os()->clearFile($log->target);
    }
}
