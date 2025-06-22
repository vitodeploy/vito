<?php

namespace App\Actions\Site;

use App\Enums\CommandExecutionStatus;
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
        Validator::make($input, self::rules($command))->validate();

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

        dispatch(function () use ($execution, $command, $log): void {
            $content = $execution->getContent();
            $execution->server_log_id = $log->id;
            $execution->save();
            $execution->server->os()->runScript(
                path: $command->site->path,
                script: $content,
                serverLog: $log,
                user: $command->site->user,
                variables: $execution->variables
            );
            $execution->status = CommandExecutionStatus::COMPLETED;
            $execution->save();
        })->catch(function () use ($execution): void {
            $execution->status = CommandExecutionStatus::FAILED;
            $execution->save();
        })->onQueue('ssh');

        return $execution;
    }

    /**
     * @return array<string, string|array<int, mixed>>
     */
    public static function rules(Command $command): array
    {
        $rules = [];
        foreach ($command->getVariables() as $variable) {
            $rules[$variable] = [
                'required',
                'string',
                'max:255',
            ];
        }

        return $rules;
    }
}
