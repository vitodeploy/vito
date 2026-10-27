<?php

namespace App\Actions\EnvironmentAttachment;

use App\Actions\Database\CreateDatabase;
use App\Enums\DatabaseStatus;
use App\Models\Database;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttachDatabase
{
    /** @param array<string, mixed> $input */
    public function attach(Environment $environment, array $input): EnvironmentAttachment
    {
        $this->validate($environment, $input);
        $server = Server::query()->findOrFail($input['server_id']);
        $service = $server->database() ?? throw ValidationException::withMessages(['server_id' => 'Install a database service on this server first.']);

        return app(AttachEnvironmentResource::class)->attach($environment, $service, $input, function () use ($server, $input): Database {
            if (! empty($input['database_id'])) {
                $database = $server->databases()->where('status', DatabaseStatus::READY)->findOrFail($input['database_id']);
                Validator::make(['database_id' => $database->name], ['database_id' => ['regex:/\A[A-Za-z0-9_-]+\z/']])->validate();

                return $database;
            }

            return app(CreateDatabase::class)->create($server, array_intersect_key($input, array_flip(['name', 'charset', 'collation'])), defer: true);
        });
    }

    /** @param array<string, mixed> $input */
    private function validate(Environment $environment, array $input): void
    {
        Validator::make($input, [
            'server_id' => ['required', 'integer', Rule::exists('servers', 'id')->where('project_id', $environment->application->project_id)],
            'database_id' => ['nullable', 'integer', Rule::exists('databases', 'id')->where('server_id', $input['server_id'] ?? null)->where('status', DatabaseStatus::READY->value)->whereNull('deleted_at')],
            'name' => ['required_without:database_id', 'nullable', 'string', 'max:63', 'regex:/\A[A-Za-z0-9_]+\z/'],
            'charset' => ['required_without:database_id', 'nullable', 'string'],
            'collation' => ['required_without:database_id', 'nullable', 'string'],
        ])->validate();
    }
}
