<?php

namespace App\Http\Resources;

use App\Models\ScriptExecution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScriptExecution */
class ScriptExecutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'script_id' => $this->script_id,
            'server_id' => $this->server_id,
            'server' => new ServerResource($this->server),
            'server_log_id' => $this->server_log_id,
            'log' => ServerLogResource::make($this->serverLog),
            'user' => $this->user,
            'variables' => $this->variables,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
