<?php

namespace App\Http\Resources;

use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Worker */
class WorkerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'name' => $this->name,
            'command' => $this->command,
            'user' => $this->user,
            'auto_start' => $this->auto_start,
            'auto_restart' => $this->auto_restart,
            'numprocs' => $this->numprocs,
            'status' => $this->status,
            'status_color' => Worker::$statusColors[$this->status] ?? 'gray',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
