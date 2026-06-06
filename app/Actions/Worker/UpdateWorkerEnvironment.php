<?php

namespace App\Actions\Worker;

use App\Exceptions\SSHError;
use App\Helpers\EnvParser;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateWorkerEnvironment
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     * @throws ValidationException
     */
    public function update(Worker $worker, array $input): WorkerEnvironmentUpdateResult
    {
        $validated = Validator::make($input, [
            ...self::rules(),
            'restart' => ['sometimes', 'boolean'],
        ])->validate();

        $worker->environment = self::processVariables($validated['variables'], $worker->environment);
        $worker->save();

        /** @var ProcessManager $processManager */
        $processManager = $worker->server->processManager()->handler();
        $processManager->writeConfig($worker);

        if ($validated['restart'] ?? false) {
            app(ManageWorker::class)->restart($worker);

            return WorkerEnvironmentUpdateResult::Restarting;
        }

        return WorkerEnvironmentUpdateResult::PendingRestart;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(string $attribute = 'variables'): array
    {
        return [
            $attribute => ['present', 'array', 'max:100'],
            ...self::nestedRules($attribute),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function nestedRules(string $attribute = 'variables'): array
    {
        return [
            "{$attribute}.*.key" => ['required', 'string', 'max:255', 'regex:/^[A-Za-z_][A-Za-z0-9_]*$/', 'distinct'],
            "{$attribute}.*.value" => ['present', 'nullable', 'string', 'max:10000', 'regex:/\A[^\x00-\x1F\x7F"]*\z/'],
            "{$attribute}.*.is_secret" => ['required', 'boolean'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $incoming
     * @param  ?array<int, array{key: string, value: string, is_secret: bool}>  $stored
     * @return array<int, array{key: string, value: string, is_secret: bool}>
     */
    public static function processVariables(array $incoming, ?array $stored): array
    {
        $normalized = array_map(fn (array $variable): array => [
            'key' => (string) $variable['key'],
            'value' => (string) ($variable['value'] ?? ''),
            'is_secret' => (bool) ($variable['is_secret'] ?? false),
        ], $incoming);

        return EnvParser::mergeWithStored($normalized, $stored);
    }
}

enum WorkerEnvironmentUpdateResult
{
    case PreFirstDeploy;
    case PendingRestart;
    case Restarting;
}
