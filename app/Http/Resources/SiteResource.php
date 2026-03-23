<?php

namespace App\Http\Resources;

use App\Enums\HostedDomainStatus;
use App\Enums\SslStatus;
use App\Models\Site;
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
            'type_data' => $this->type_data,
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
            'url' => $this->getUrl(),
            'force_ssl' => $this->force_ssl,
            'ssl_enabled' => $this->ssl_enabled,
            'progress' => $this->progress,
            'features' => $this->features(),
            'can_configure_ssl' => $this->webserver()->canConfigureSSL(),
            'webserver_allowed_ssl_methods' => $this->webserver()->allowedSslMethods(),
            'webserver_default_ssl_method' => $this->webserver()->defaultSslMethod()->value,
            'vhost_generation_enabled' => $this->vhost_generation_enabled,
            'modern_deployment' => $this->modernDeploymentEnabled(),
            'warnings' => $this->getWarnings(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array<int, array{key: string, ...}>
     */
    private function getWarnings(): array
    {
        $warnings = [];

        $hostedDomains = $this->relationLoaded('hostedDomains') ? $this->hostedDomains : collect();

        $pendingDomains = $hostedDomains->where('status', HostedDomainStatus::PENDING);
        if ($pendingDomains->isNotEmpty()) {
            $warnings[] = [
                'key' => 'pending_domains',
                'count' => $pendingDomains->count(),
                'domains' => $pendingDomains->pluck('domain')->all(),
            ];
        }

        if (! $this->ssl_enabled) {
            $warnings[] = ['key' => 'ssl_disabled'];
        }

        if (! $this->vhost_generation_enabled) {
            $warnings[] = ['key' => 'vhost_generation_disabled'];
        }

        $expiring = $hostedDomains->filter(
            fn ($hd) => $hd->ssl_id
                && $hd->relationLoaded('ssl')
                && $hd->ssl
                && $hd->ssl->status === SslStatus::CREATED
                && $hd->ssl->expires_at
                && $hd->ssl->expires_at <= now()->addDays(14)
        );

        if ($expiring->isNotEmpty()) {
            $earliestExpiry = $expiring->min(fn ($hd) => $hd->ssl->expires_at);
            $warnings[] = [
                'key' => 'ssl_expiring',
                'count' => $expiring->count(),
                'domains' => $expiring->pluck('domain')->all(),
                'earliest_expiry' => $earliestExpiry?->toIso8601String(),
            ];
        }

        return $warnings;
    }
}
