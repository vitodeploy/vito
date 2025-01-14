<?php

namespace App\Http\Resources;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Site */
class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'source_control_id' => $this->source_control_id,
            'type' => $this->type,
            'type_data' => $this->type_data,
            'domain' => $this->domain,
            'aliases' => $this->aliases,
            'web_directory' => $this->web_directory,
            'path' => $this->path,
            'php_version' => $this->php_version,
            'repository' => $this->repository,
            'branch' => $this->branch,
            'status' => $this->status,
            'port' => $this->port,
            'user' => $this->user,
            'progress' => $this->progress,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
