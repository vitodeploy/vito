<?php

namespace App\Actions\Server;

use App\DTOs\ServiceLog;
use App\Models\Server;
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

        if ($log->source !== ServiceLog::SOURCE_FILE) {
            throw ValidationException::withMessages(['key' => 'Journal logs cannot be cleared.']);
        }

        $server->os()->clearFile($log->target);
    }
}
