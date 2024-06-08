<?php

namespace App\Actions\Script;

use App\Enums\ScriptExecutionStatus;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExecuteScript
{
    public function execute(Script $script, Server $server, array $input): ScriptExecution
    {
        $this->validate($server, $input);

        $execution = new ScriptExecution([
            'script_id' => $script->id,
            'user' => $input['user'],
            'variables' => $input['variables'] ?? [],
            'status' => ScriptExecutionStatus::EXECUTING,
        ]);
        $execution->save();

        dispatch(function () use ($execution, $server, $script) {
            $content = $execution->getContent();
            $log = ServerLog::make($server, 'script-'.$script->id.'-'.strtotime('now'));
            $log->save();
            $execution->server_log_id = $log->id;
            $execution->save();
            $server->os()->runScript('~/', $content, $log, $execution->user);
            $execution->status = ScriptExecutionStatus::COMPLETED;
            $execution->save();
        })->catch(function () use ($execution) {
            $execution->status = ScriptExecutionStatus::FAILED;
            $execution->save();
        })->onConnection('ssh');

        return $execution;
    }

    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'user' => [
                'required',
                Rule::in([
                    'root',
                    $server->ssh_user,
                ]),
            ],
            'variables' => 'array',
            'variables.*' => [
                'required',
                'string',
                'max:255',
            ],
        ])->validate();
    }
}
