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
            'charset' => 'The character set of the database',
            'collation' => 'The collation of the database',
        ];
    }

    public function outputs(): array
    {
        return [
            'server_id' => 'The ID of the server where the database was created',
            'database_name' => 'The name of the created database',
            'database_id' => 'The ID of the created database',
        ];
    }

    public function run(array $input): array
    {
        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);

        $this->authorize('create', [Database::class, $server]);

        $database = app(\App\Actions\Database\CreateDatabase::class)->create($server, $input);

        return [
            'server_id' => $server->id,
            'database_name' => $database->name,
            'database_id' => $database->id,
        ];
    }
}
