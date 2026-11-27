<?php

namespace App\WorkflowActions\Database;

use App\DTOs\DynamicField;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\WorkflowActions\AbstractWorkflowAction;

class CreateDatabaseUser extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            DynamicField::make('server_id')->resource('server')->label('Server'),
            DynamicField::make('username')->text()->label('Username'),
            DynamicField::make('password')->passwordWithToggle()->label('Password'),
            DynamicField::make('remote')->checkbox()->label('Allow remote access')->default(false),
            DynamicField::make('host')->text()->label('Host')->placeholder('%')->description('Required for remote access, % allows any host'),
            DynamicField::make('databases')->tags()->label('Databases')->default([])->placeholder('Database name')->description('Names of the databases to link'),
            DynamicField::make('permission')->select()->label('Permission')->options(['read', 'write', 'admin'])->default('admin'),
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
