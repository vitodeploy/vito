<?php

namespace App\DTOs;

use App\Exceptions\AppError;
use App\Models\User;
use App\Models\Workflow;
use App\WorkflowActions\WorkflowActionInterface;

class WorkflowActionDTO
{
    /**
     * @param  array<int, string>  $outputs
     * @param  array<string, mixed>  $inputs
     */
    public function __construct(
        public string $label,
        public string $handler,
        public array $outputs,
        public array $inputs,
        public string $id,
        public ?WorkflowActionDTO $success = null,
        public ?WorkflowActionDTO $failure = null,
    ) {}

    public function handler(User $user, Workflow $workflow): WorkflowActionInterface
    {
        $handlerClass = $this->handler;

        if (! in_array($handlerClass, self::registeredHandlers(), true)) {
            throw new AppError("Handler class {$handlerClass} is not a registered workflow action.");
        }

        return new $handlerClass($user, $workflow);
    }

    /**
     * @return list<string>
     */
    public static function registeredHandlers(): array
    {
        return array_values(array_filter(array_column(config('workflow.actions', []), 'handler')));
    }

    /**
     * @param  array<string, mixed>  $actionData
     */
    public static function fromArray(array $actionData, string $nodeId): self
    {
        return new self(
            label: $actionData['label'] ?? '',
            handler: $actionData['handler'] ?? '',
            outputs: array_keys($actionData['outputs'] ?? []),
            inputs: $actionData['inputs'] ?? [],
            id: $nodeId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'run' => [
                'label' => $this->label,
                'handler' => $this->handler,
                'outputs' => $this->outputs,
                'inputs' => $this->inputs,
                'id' => $this->id,
            ],
            'success' => $this->success?->toArray(),
            'failure' => $this->failure?->toArray(),
        ];
    }
}
