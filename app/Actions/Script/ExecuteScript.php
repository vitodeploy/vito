<?php

namespace App\Actions\Script;

use App\Enums\ScriptExecutionStatus;
use App\Jobs\Script\ExecuteJob;
use App\Models\Script;
use App\Models\ScriptEventHook;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExecuteScript
{
    /**
     * Execute a script triggered by an event hook, injecting event context as variables.
     *
     * @param  array<string, string>  $eventVariables
     */
    public function executeForHook(ScriptEventHook $hook, array $eventVariables): ScriptExecution
    {
        $script = $hook->script;

        $variables = [];
        foreach ($script->getVariables() as $variable) {
            $variables[$variable] = $this->sanitizeVariable($eventVariables[$variable] ?? '');
        }

        return $this->startExecution($script, $hook->server, $hook->user, $variables);
    }

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

        return $this->startExecution($script, $server, $input['user'], $variables);
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function startExecution(Script $script, Server $server, string $user, array $variables): ScriptExecution
    {
        /** @var array{0: ScriptExecution, 1: ServerLog} $result */
        $result = DB::transaction(function () use ($script, $server, $user, $variables): array {
            $execution = new ScriptExecution([
                'script_id' => $script->id,
                'server_id' => $server->id,
                'user' => $user,
                'variables' => $variables,
                'status' => ScriptExecutionStatus::EXECUTING,
            ]);
            $execution->save();

            $log = ServerLog::newLog($server, 'script-'.$script->id.'-'.strtotime('now'));
            $log->save();

            $execution->server_log_id = $log->id;
            $execution->save();

            return [$execution, $log];
        });

        [$execution, $log] = $result;

        dispatch(new ExecuteJob($execution, $log))->onQueue('ssh');

        return $execution;
    }

    private function sanitizeVariable(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9 ._\-\/:@]/', '', $value) ?? '';
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
