<?php

namespace App\Http\Resources;

use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Server */
class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'provider_id' => $this->provider_id,
            'name' => $this->name,
            'ssh_user' => $this->ssh_user,
            'ip' => $this->ip,
            'local_ip' => $this->local_ip,
            'port' => $this->port,
            'os' => $this->os,
            'type' => $this->type,
            'type_data' => $this->type_data,
            'provider' => $this->provider,
            'provider_data' => $this->provider_data,
            'public_key' => $this->public_key,
            'status' => $this->status,
            'auto_update' => $this->auto_update,
            'available_updates' => $this->available_updates,
            'security_updates' => $this->security_updates,
            'progress' => $this->progress,
            'progress_step' => $this->progress_step,
            'updates' => $this->updates,
            'last_update_check' => $this->last_update_check,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
