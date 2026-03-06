<?php

namespace App\DTOs;

final readonly class SocketEventDTO
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public int $projectId,
        public string $type,
        public array $data,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
