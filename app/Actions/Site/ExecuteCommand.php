<?php

namespace App\Actions\Site;

use App\Enums\CommandExecutionStatus;
use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\ServerLog;
use App\Models\User;

class ExecuteCommand
{
    /**
     * @param  array<string, mixed>  $input
     */
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

        dispatch(function () use ($execution, $command): void {
            $content = $execution->getContent();
            $log = ServerLog::newLog($execution->server, 'command-'.$command->id.'-'.strtotime('now'));
            $log->save();
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
        })->onConnection('ssh');

        return $execution;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|array<int, mixed>>
     */
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
