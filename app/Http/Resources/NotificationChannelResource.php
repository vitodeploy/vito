<?php

namespace App\Http\Resources;

use App\Models\NotificationChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationChannel */
class NotificationChannelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'global' => is_null($this->project_id),
            'name' => $this->label,
            'provider' => $this->provider,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
