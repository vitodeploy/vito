<?php

namespace App\Http\Resources;

use App\Models\DatabaseUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DatabaseUser */
class DatabaseUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'username' => $this->username,
            'databases' => $this->databases,
            'host' => $this->host,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
