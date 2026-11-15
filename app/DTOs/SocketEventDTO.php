<?php

namespace App\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final readonly class SocketEventDTO
{
    /** @var array<string, mixed> */
    public array $data;

    /**
     * @param  array<string, mixed>|JsonResource  $data
     */
    public function __construct(
        public int $projectId,
        public string $type,
        array|JsonResource $data,
        public ?int $userId = null,
    ) {
        $this->data = $data instanceof JsonResource
            ? $data->resolve(new Request)
            : $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'user_id' => $this->userId,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
