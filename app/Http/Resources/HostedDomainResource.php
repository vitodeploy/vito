<?php

namespace App\Http\Resources;

use App\Models\HostedDomain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HostedDomain */
class HostedDomainResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'server_id' => $this->site->server_id,
            'domain' => $this->domain,
            'type' => $this->type->getText(),
            'type_color' => $this->type->getColor(),
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'ssl_method' => $this->ssl_method->getText(),
            'ssl_id' => $this->ssl_id,
            'error' => $this->error,
            'ssl_type' => $this->ssl?->type,
            'ssl_domains' => $this->ssl?->domains,
            'ssl_expires_at' => $this->ssl?->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
