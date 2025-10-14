<?php

namespace App\WorkflowActions\Database;

use App\Models\Database;
use App\Models\Server;
use App\WorkflowActions\AbstractWorkflowAction;

class CreateDatabase extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'server_id' => 'The ID of the server to create the database on',
            'name' => 'The name of the database to create',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'username' => 'The username of the database user (optional)',
            'password' => 'The password of the database user (optional)',
        ];
    }

    public function outputs(): array
    {
        return [
            'server_id' => 'The ID of the server where the database was created',
            'database_name' => 'The name of the created database',
            'database_id' => 'The ID of the created database',
            'database_user_id' => 'The ID of the created database user (if a user was created)',
            'database_user_username' => 'The name of the created database user (if a user was created)',
        ];
    }

    public function run(array $input): array
    {
        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);

        $this->authorize('create', [Database::class, $server]);

        $database = app(\App\Actions\Database\CreateDatabase::class)->create($server, $input);

        $outputs = [
            'server_id' => $server->id,
            'database_name' => $database->name,
            'database_id' => $database->id,
        ];
        if (isset($input['username']) && $input['username']) {
            $databaseUser = $server->databaseUsers()->where('username', $input['username'])->first();
            if ($databaseUser) {
                $outputs['database_user_id'] = $databaseUser->id;
                $outputs['database_user_username'] = $databaseUser->username;
            }
        }

        return $outputs;
    }
}
