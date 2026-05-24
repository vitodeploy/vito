<?php

namespace App\Actions\Server;

use App\DTOs\ServiceLog;
use App\Models\Server;
use App\SSH\OS\OS;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DownloadServiceLog
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     * @throws ValidationException
     */
    public function run(Server $server, array $input): StreamedResponse
    {
        $data = Validator::make($input, [
            'key' => ['required', 'string', 'max:200'],
        ])->validate();

        $log = app(GetServiceLogs::class)->resolve($server, $data['key']);
        abort_if($log === null, 404);

        $downloadName = $log->source === ServiceLog::SOURCE_JOURNAL
            ? Str::slug($log->key).'.log'
            : str($log->target)->afterLast('/')->toString();

        $tmpName = $server->id.'-'.now()->timestamp.'-'.Str::random(8).'-'.Str::slug($log->key).'.log';
        $tmpPath = Storage::disk('local')->path($tmpName);

        $remoteTmp = '/tmp/vito-'.Str::random(12).'.log';
        try {
            if ($log->source === ServiceLog::SOURCE_JOURNAL) {
                $server->ssh()->exec(view('ssh.os.journal-dump', [
                    'unit' => $log->target,
                    'path' => $remoteTmp,
                ]));
            } else {
                $output = $server->ssh()->exec(view('ssh.os.copy-as-user', [
                    'source' => $log->target,
                    'dest' => $remoteTmp,
                ]));
                abort_if(
                    trim($output) === OS::FILE_NOT_FOUND,
                    404,
                    'The log file does not exist on the server.'
                );
            }
            $server->ssh()->download($tmpPath, $remoteTmp);
        } finally {
            try {
                $server->os()->deleteFile($remoteTmp);
            } catch (Throwable) {
            }
        }

        dispatch(function () use ($tmpPath): void {
            if (File::exists($tmpPath)) {
                File::delete($tmpPath);
            }
        })
            ->delay(now()->addMinutes(5))
            ->onQueue('default');

        return Storage::disk('local')->download($tmpName, $downloadName);
    }
}
