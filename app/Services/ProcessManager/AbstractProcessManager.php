<?php

namespace App\Services\ProcessManager;

use App\Services\AbstractService;
use Closure;

abstract class AbstractProcessManager extends AbstractService implements ProcessManager
{
    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
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
                function (string $attribute, mixed $value, Closure $fail): void {
                    $hasWorker = $this->service->server->workers()->exists();
                    if ($hasWorker) {
                        $fail('You have worker(s) on the server.');
                    }
                },
            ],
        ];
    }
}
