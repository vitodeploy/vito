<?php

namespace App\Actions\Site;

use App\Enums\CommandExecutionStatus;
use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\ServerLog;
use App\Models\User;

class ExecuteCommand
{
    public function execute(Command $command, User $user, array $input): CommandExecution
    {
        $execution = new CommandExecution([
            'command_id' => $command->id,
            'server_id' => $command->site->server_id,
            'user_id' => $user->id,
            'variables' => $input['variables'] ?? [],
            'status' => CommandExecutionStatus::EXECUTING,
        ]);
        $execution->save();

        dispatch(function () use ($execution, $command) {
            $content = $execution->getContent();
            $log = ServerLog::newLog($execution->server, 'command-'.$command->id.'-'.strtotime('now'));
            $log->save();
            $execution->server_log_id = $log->id;
            $execution->save();
            $execution->server->os()->runScript(
                path: $command->site->path,
                script: $content,
                user: $command->site->user,
                serverLog: $log,
                variables: $execution->variables
            );
            $execution->status = CommandExecutionStatus::COMPLETED;
            $execution->save();
        })->catch(function () use ($execution) {
            $execution->status = CommandExecutionStatus::FAILED;
            $execution->save();
        })->onConnection('ssh');

        return $execution;
    }

    public static function rules(array $input): array
    {
        return [
            'variables' => 'array',
            'variables.*' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
