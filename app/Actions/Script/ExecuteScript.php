<?php

namespace App\Actions\Script;

use App\Enums\ScriptExecutionStatus;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExecuteScript
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(Script $script, User $user, array $input): ScriptExecution
    {
        Validator::make($input, self::rules($script, $input))->validate();

        $variables = [];
        foreach ($script->getVariables() as $variable) {
            if (array_key_exists($variable, $input)) {
                $variables[$variable] = $input[$variable] ?? '';
            }
        }

        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server']);

        if (! $user->can('update', $server)) {
            abort(403, 'You do not have permission to execute scripts on this server.');
        }

        $execution = new ScriptExecution([
            'script_id' => $script->id,
            'server_id' => $input['server'],
            'user' => $input['user'],
            'variables' => $variables,
            'status' => ScriptExecutionStatus::EXECUTING,
        ]);
        $execution->save();

        $log = ServerLog::newLog($execution->server, 'script-'.$script->id.'-'.strtotime('now'));
        $log->save();

        $execution->server_log_id = $log->id;
        $execution->save();

        dispatch(function () use ($execution, $log): void {
            /** @var Server $server */
            $server = $execution->server;

            $content = $execution->getContent();
            $execution->server_log_id = $log->id;
            $execution->save();
            $server->os()->runScript('~/', $content, $log, $execution->user);
            $execution->status = ScriptExecutionStatus::COMPLETED;
            $execution->save();
        })->catch(function () use ($execution): void {
            $execution->status = ScriptExecutionStatus::FAILED;
            $execution->save();
        })->onQueue('ssh');

        return $execution;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(Script $script, array $input): array
    {
        $users = ['root'];
        if (isset($input['server'])) {
            /** @var Server $server */
            $server = Server::query()->findOrFail($input['server']);
            $users = $server->getSshUsers();
        }

        $rules = [
            'server' => [
                'required',
                Rule::exists('servers', 'id'),
            ],
            'user' => [
                'required',
                Rule::in($users),
            ],
        ];

        foreach ($script->getVariables() as $variable) {
            $rules[$variable] = [
                'required',
                'string',
                'max:255',
            ];
        }

        return $rules;
    }
}
