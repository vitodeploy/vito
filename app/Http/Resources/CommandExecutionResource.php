<?php

namespace App\Http\Resources;

use App\Models\CommandExecution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CommandExecution */
class CommandExecutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'command_id' => $this->command_id,
            'server_id' => $this->server_id,
            'user_id' => $this->user_id,
            'server_log_id' => $this->server_log_id,
            'log' => ServerLogResource::make($this->serverLog),
            'variables' => $this->variables,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
