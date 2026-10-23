<?php

namespace App\Http\Resources;

use App\Models\Environment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Environment */
class EnvironmentSummaryResource extends JsonResource
{
    /** @return array{id: int, name: string, branch: string, status: string, status_color: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'branch' => $this->branch,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
        ];
    }
}
