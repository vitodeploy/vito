<?php

namespace App\Http\Resources;

use App\Models\Backup;
use App\Models\BackupFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BackupFile */
class BackupFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'backup_id' => $this->backup_id,
            'name' => $this->name,
            'size' => $this->size,
            'restored_to' => $this->restored_to,
            'restored_at' => $this->restored_at,
            'status' => $this->status,
            'status_color' => Backup::$statusColors[$this->status] ?? 'outline',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
