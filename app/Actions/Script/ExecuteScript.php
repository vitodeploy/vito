<?php

namespace App\Actions\Script;

use App\Enums\ScriptExecutionStatus;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Site;
use Illuminate\Validation\Rule;

class ExecuteScript
{
    public function execute(Script $script, array $input): ScriptExecution
    {
        $execution = new ScriptExecution([
            'script_id' => $script->id,
            'server_id' => $input['server'],
            'user' => $input['user'],
            'variables' => $input['variables'] ?? [],
            'status' => ScriptExecutionStatus::EXECUTING,
        ]);
        $execution->save();

        $server = Server::query()->find($input['server']);
        $user = in_array($input['user'], ['root', $server->ssh_user])
            ? $input['user']
            : $server->ssh_user;
        $runAsUser = ! in_array($input['user'], ['root', $server->ssh_user])
            ? $input['user']
            : null;

        dispatch(function () use ($execution, $script, $user, $runAsUser) {
            $content = $execution->getContent();
            $log = ServerLog::make($execution->server, 'script-'.$script->id.'-'.strtotime('now'));
            $log->save();
            $execution->server_log_id = $log->id;
            $execution->save();
            $execution->server->os()->runScript('~/', $content, $log, $user, $runAsUser);
            $execution->status = ScriptExecutionStatus::COMPLETED;
            $execution->save();
        })->catch(function () use ($execution) {
            $execution->status = ScriptExecutionStatus::FAILED;
            $execution->save();
        })->onConnection('ssh');

        return $execution;
    }

    public static function rules(array $input): array
    {
        if (isset($input['server'])) {
            /** @var ?Server $server */
            $server = Server::query()->find($input['server']);
        }

        return [
            'server' => [
                'required',
                Rule::exists('servers', 'id'),
            ],
            'user' => [
                'required',
                Rule::in(array_merge(
                    ['root'],
                    isset($server) ? [$server->ssh_user] : [],
                    isset($server) ? Site::query()
                        ->whereNot('user', value: $server->ssh_user)
                        ->whereServerId($server->id)
                        ->pluck('user')
                        ->toArray() : []
                )),
            ],
            'variables' => 'array',
            'variables.*' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
