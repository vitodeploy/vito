<?php

namespace App\DTOs;

use App\Exceptions\AppError;
use App\Models\User;
use App\Models\Workflow;
use App\WorkflowActions\WorkflowActionInterface;

readonly class WorkflowActionDTO
{
    /**
     * @param  array<int, string>  $outputs
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $label,
        public string $handler,
        public array $outputs,
        public array $data,
        public array $form,
        public string $id,
        public ?WorkflowActionDTO $success = null,
        public ?WorkflowActionDTO $failure = null,
    ) {}

    public function handler(User $user, Workflow $workflow): WorkflowActionInterface
    {
        $handlerClass = $this->handler;

        if (! class_exists($handlerClass)) {
            throw new AppError("Handler class {$handlerClass} does not exist.");
        }

        return new $handlerClass($user, $workflow);
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
            data: $actionData['data'] ?? [],
            form: $actionData['form'] ?? [],
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
                'data' => $this->data,
                'form' => $this->form,
                'id' => $this->id,
            ],
            'success' => $this->success?->toArray(),
            'failure' => $this->failure?->toArray(),
        ];
    }
}
