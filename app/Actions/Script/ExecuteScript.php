<?php

namespace App\Actions\Script;

use App\Enums\ScriptExecutionStatus;
use App\Jobs\Script\ExecuteJob;
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
        $this->validate($script, $input);

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

        dispatch(new ExecuteJob($execution, $log))->onQueue('ssh');

        return $execution;
    }

    private function validate(Script $script, array $input): void
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

        Validator::make($input, $rules)->validate();
    }
}
