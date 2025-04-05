<?php

namespace App\Http\Resources;

use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Redirect */
class RedirectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'mode' => $this->mode,
            'from' => $this->from,
            'to' => $this->to,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
