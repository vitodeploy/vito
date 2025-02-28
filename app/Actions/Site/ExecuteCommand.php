<?php

namespace App\Actions\Site;

use App\Enums\CommandExecutionStatus;
use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Validation\Rule;

class ExecuteCommand
{
    public function execute(Command $command, array $input): CommandExecution
    {
        $execution = new CommandExecution([
            'command_id' => $command->id,
            'server_id' => $command->site->server_id,
            'user' => $command->site->user,
            'variables' => $input['variables'] ?? [],
            'status' => CommandExecutionStatus::EXECUTING,
        ]);
        $execution->save();

        dispatch(function () use ($execution, $command) {
            $content = $execution->getContent();
            $log = ServerLog::make($execution->server, 'command-'.$command->id.'-'.strtotime('now'));
            $log->save();
            $execution->server_log_id = $log->id;
            $execution->save();
            $execution->server->os()->runCommand($command->site->path, $content, $log, $execution->user);
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
