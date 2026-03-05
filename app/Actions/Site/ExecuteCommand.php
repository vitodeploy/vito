<?php

namespace App\Actions\Site;

use App\Enums\CommandExecutionStatus;
use App\Jobs\Site\ExecuteCommandJob;
use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\ServerLog;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ExecuteCommand
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(Command $command, User $user, array $input): CommandExecution
    {
        $this->validate($command, $input);

        $variables = [];
        foreach ($command->getVariables() as $variable) {
            if (array_key_exists($variable, $input)) {
                $variables[$variable] = $input[$variable] ?? '';
            }
        }

        $execution = new CommandExecution([
            'command_id' => $command->id,
            'server_id' => $command->site->server_id,
            'user_id' => $user->id,
            'variables' => $variables,
            'status' => CommandExecutionStatus::EXECUTING,
        ]);
        $execution->save();

        $log = ServerLog::newLog($execution->server, 'command-'.$command->id.'-'.strtotime('now'));
        $log->save();
        $execution->server_log_id = $log->id;
        $execution->save();

        dispatch(new ExecuteCommandJob($execution, $command, $log))->onQueue('ssh');

        return $execution;
    }

    private function validate(Command $command, array $input): void
    {
        $rules = [];
        foreach ($command->getVariables() as $variable) {
            $rules[$variable] = [
                'required',
                'string',
                'max:255',
            ];
        }

        Validator::make($input, $rules)->validate();
    }
}
