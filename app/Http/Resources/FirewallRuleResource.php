<?php

namespace App\Http\Resources;

use App\Models\FirewallRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FirewallRule */
class FirewallRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'server_id' => $this->server_id,
            'type' => $this->type,
            'protocol' => $this->protocol,
            'port' => $this->port,
            'source' => $this->source,
            'mask' => $this->mask,
            'note' => $this->note,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
