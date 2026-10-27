<?php

namespace App\Http\Resources;

use App\Models\EnvironmentDomain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnvironmentDomain */
class EnvironmentDomainResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'component_id' => $this->component_id,
            'host' => $this->host,
            'type' => $this->type->getText(),
            'type_color' => $this->type->getColor(),
            'redirect_to_domain_id' => $this->redirect_to_domain_id,
            'ssl_method' => $this->ssl_method->getText(),
            'ssl_method_color' => $this->ssl_method->getColor(),
            'ssl_id' => $this->ssl_id,
            'force_ssl' => $this->force_ssl,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'error' => $this->error,
        ];
    }
}
