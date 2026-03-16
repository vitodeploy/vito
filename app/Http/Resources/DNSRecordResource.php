<?php

namespace App\Http\Resources;

use App\Models\DNSRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DNSRecord */
class DNSRecordResource extends JsonResource
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
            'type' => $this->type,
            'name' => $this->name,
            'formatted_name' => $this->formatted_name,
            'content' => $this->content,
            'ttl' => $this->ttl,
            'proxied' => $this->proxied,
            'priority' => $this->priority,
            'domain_id' => $this->domain_id,
            'domain' => new DomainResource($this->whenLoaded('domain')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
