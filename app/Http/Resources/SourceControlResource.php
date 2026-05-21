<?php

namespace App\Http\Resources;

use App\Models\SourceControl;
use App\SourceControlProviders\SourceControlProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SourceControl */
class SourceControlResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $handler = config('source-control.providers.'.$this->provider.'.handler');
        $supportsSshPort = is_string($handler)
            && is_a($handler, SourceControlProvider::class, true)
            && in_array('ssh_port', $handler::editableFields(), true);

        $data = [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'global' => is_null($this->project_id),
            'name' => $this->profile,
            'provider' => $this->provider,
            'external_identifier' => $this->external_identifier,
            'ssh_port' => $this->when($supportsSshPort, fn () => $this->provider()->getSshPort()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->isGithubApp()) {
            $providerData = $this->provider_data ?? [];
            $data['github_app'] = [
                'account_login' => $providerData['account_login'] ?? null,
                'account_type' => $providerData['account_type'] ?? null,
                'html_url' => $providerData['html_url'] ?? null,
            ];
        }

        return $data;
    }
}
