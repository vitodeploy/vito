<?php

namespace App\Actions\Server;

use App\DTOs\ServiceLog;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\SSH\OS\OS;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReadServiceLog
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{content: string, display_target: string, source: string}
     *
     * @throws SSHError
     * @throws ValidationException
     */
    public function run(Server $server, array $input): array
    {
        $data = Validator::make($input, [
            'key' => ['required', 'string', 'max:200'],
            'lines' => ['nullable', 'integer', 'min:50', 'max:2000'],
            'search' => ['nullable', 'string', 'max:200', 'regex:/^[^\x00\r\n]*$/'],
        ])->validate();

        $log = app(GetServiceLogs::class)->resolve($server, $data['key']);
        abort_if($log === null, 404);

        $lines = (int) ($data['lines'] ?? 100);
        $search = $data['search'] ?? null;
        $hasSearch = $search !== null && $search !== '';

        if ($log->source === ServiceLog::SOURCE_JOURNAL) {
            $content = $server->ssh()->exec(view('ssh.os.journal-read', [
                'unit' => $log->target,
                'lines' => $lines,
                'search' => $hasSearch ? $search : null,
            ]));
        } elseif ($hasSearch) {
            $content = $server->ssh()->exec(view('ssh.os.grep', [
                'path' => $log->target,
                'term' => $search,
                'lines' => $lines,
            ]));
        } else {
            $content = $server->os()->tail($log->target, $lines);
        }

        abort_if(
            $log->source === ServiceLog::SOURCE_FILE && trim($content) === OS::FILE_NOT_FOUND,
            404,
            'The log file does not exist on the server.'
        );

        return [
            'content' => $content,
            'display_target' => $log->displayTarget(),
            'source' => $log->source,
        ];
    }
}
