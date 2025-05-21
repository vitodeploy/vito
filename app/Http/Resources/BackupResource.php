<?php

namespace App\Http\Resources;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Backup */
class BackupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'storage_id' => $this->storage_id,
            'storage' => StorageProviderResource::make($this->storage),
            'database_id' => $this->database_id,
            'database' => DatabaseResource::make($this->database),
            'type' => $this->type,
            'keep_backups' => $this->keep_backups,
            'interval' => $this->interval,
            'files_count' => $this->files_count,
            'status' => $this->status,
            'last_file' => BackupFileResource::make($this->whenLoaded('lastFile')),
            'status_color' => Backup::$statusColors[$this->status] ?? 'outline',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
