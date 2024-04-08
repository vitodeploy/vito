<?php

namespace App\SSH\Services\ProcessManager;

use App\SSH\Services\AbstractService;
use Closure;

abstract class AbstractProcessManager extends AbstractService implements ProcessManager
{
    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    $processManagerExists = $this->service->server->processManager();
                    if ($processManagerExists) {
                        $fail('You already have a process manager service on the server.');
                    }
                },
            ],
        ];
    }

    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail) {
                    $hasQueue = $this->service->server->queues()->exists();
                    if ($hasQueue) {
                        $fail('You have queue(s) on the server.');
                    }
                },
            ],
        ];
    }
}
