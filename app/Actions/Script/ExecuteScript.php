<?php

namespace App\Actions\Script;

use App\Enums\ScriptExecutionStatus;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\ServerLog;
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

        dispatch(function () use ($execution, $script) {
            $content = $execution->getContent();
            $log = ServerLog::make($execution->server, 'script-'.$script->id.'-'.strtotime('now'));
            $log->save();
            $execution->server_log_id = $log->id;
            $execution->save();
            $execution->server->os()->runScript('~/', $content, $log, $execution->user);
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
        $users = ['root'];
        if (isset($input['server'])) {
            /** @var ?Server $server */
            $server = Server::query()->find($input['server']);
            $users = $server->getSshUsers();
        }

        return [
            'server' => [
                'required',
                Rule::exists('servers', 'id'),
            ],
            'user' => [
                'required',
                Rule::in($users),
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
