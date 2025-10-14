<?php

namespace App\WorkflowActions\Database;

use App\Models\DatabaseUser;
use App\Models\Server;
use App\WorkflowActions\AbstractWorkflowAction;

class CreateDatabaseUser extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'server_id' => 'The ID of the server to create the database on',
            'username' => 'The username of the database user (optional)',
            'password' => 'The password of the database user (optional)',
            'remote' => 'If true, you must provide the host, if false remove host field.',
            'host' => 'The host the user can connect from, default is % which allows connection from any host',
            'databases' => [
                'database-1',
                'database-2',
                'send this field empty [] if you do not want to assign any databases yet',
            ],
            'permission' => 'The permission level for the user. (read, write, admin)',
        ];
    }

    public function outputs(): array
    {
        return [
            'server_id' => 'The ID of the server where the database was created',
            'database_user_id' => 'The ID of the created database user',
            'database_user_username' => 'The name of the created database user',
            'database_user_host' => 'The host of the created database user',
            'database_user_databases' => 'The databases assigned to the created database user',
            'database_user_permission' => 'The permission level of the created database user',
            'database_user_status' => 'The status of the created database user',
        ];
    }

    public function run(array $input): array
    {
        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);

        $this->authorize('create', [DatabaseUser::class, $server]);

        $databaseUser = app(\App\Actions\Database\CreateDatabaseUser::class)->create($server, $input, $input['databases'] ?? []);

        return [
            'server_id' => $server->id,
            'database_user_id' => $databaseUser->id,
            'database_user_username' => $databaseUser->username,
            'database_user_host' => $databaseUser->host,
            'database_user_databases' => $databaseUser->databases,
            'database_user_permission' => $databaseUser->permission,
            'database_user_status' => $databaseUser->status,
        ];
    }
}
