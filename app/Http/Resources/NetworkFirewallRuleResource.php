<?php

namespace App\Http\Resources;

use App\Models\NetworkFirewallRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NetworkFirewallRule */
class NetworkFirewallRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'network_id' => $this->network_id,
            'name' => $this->name,
            'protocol' => $this->protocol,
            'port' => $this->port,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
