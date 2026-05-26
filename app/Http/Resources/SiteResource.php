<?php

namespace App\Http\Resources;

use App\Models\Site;
use App\SiteTypes\AbstractProxiedSiteType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Site */
class SiteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'server' => new ServerResource($this->whenLoaded('server')),
            'source_control_id' => $this->source_control_id,
            'type' => $this->type,
            'type_data' => $this->sanitisedTypeData(),
            'basic_auth' => [
                'enabled' => (bool) data_get($this->type_data, 'basic_auth.enabled', false),
                'users' => array_map(
                    fn (array $u) => ['username' => $u['username'] ?? ''],
                    array_values(data_get($this->type_data, 'basic_auth.users', []))
                ),
            ],
            'domain' => $this->domain,
            'web_directory' => $this->web_directory,
            'webserver' => $this->webserver()->id(),
            'webserver_creates_site_ssls' => $this->webserver()->createsSiteSSLs(),
            'path' => $this->path,
            'php_version' => $this->php_version,
            'repository' => $this->repository,
            'branch' => $this->branch,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'auto_deploy' => $this->isAutoDeployment(),
            'port' => $this->port,
            'user' => $this->user,
            'isolated_user_id' => $this->isolated_user_id,
            'url' => $this->getUrl(),
            'force_ssl' => $this->force_ssl,
            'ssl_enabled' => $this->ssl_enabled,
            'progress' => $this->progress,
            'progress_step' => $this->progress_step,
            'last_error' => $this->last_error,
            'features' => $this->features(),
            'can_configure_ssl' => $this->webserver()->canConfigureSSL(),
            'webserver_allowed_ssl_methods' => $this->webserver()->allowedSslMethods(),
            'webserver_default_ssl_method' => $this->webserver()->defaultSslMethod()->value,
            'vhost_generation_enabled' => $this->vhost_generation_enabled,
            'has_custom_vhost_template' => $this->vhost_template !== null,
            'modern_deployment' => $this->modernDeploymentEnabled(),
            'stats_enabled' => $this->statsEnabled(),
            'is_proxied_site_type' => $this->type() instanceof AbstractProxiedSiteType,
            'available_tooling_commands' => $this->availableToolingCommands(),
            'start_command' => $this->type_data['start_command'] ?? null,
            'bootstrap_worker_id' => isset($this->type_data['bootstrap_worker_id'])
                ? (int) $this->type_data['bootstrap_worker_id']
                : null,
            'warnings' => $this->getWarnings(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Strip basic-auth password hashes from type_data before sending to the client.
     *
     * @return array<string, mixed>
     */
    private function sanitisedTypeData(): array
    {
        $typeData = $this->type_data ?? [];

        if (isset($typeData['basic_auth']['users']) && is_array($typeData['basic_auth']['users'])) {
            $typeData['basic_auth']['users'] = array_map(
                fn (array $u) => ['username' => $u['username'] ?? ''],
                $typeData['basic_auth']['users']
            );
        }

        return $typeData;
    }
}
