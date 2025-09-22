<?php

namespace App\Http\Resources;

use App\Models\VitoBackup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VitoBackup
 */
class VitoBackupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'frequency' => $this->frequency,
            'keep_backups' => $this->keep_backups,
            'status' => $this->status,
            'storage' => $this->whenLoaded('storage', function () {
                return [
                    'id' => $this->storage->id,
                    'profile' => $this->storage->profile,
                    'provider' => $this->storage->provider,
                ];
            }),
            'files' => VitoBackupFileResource::collection($this->whenLoaded('files')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
