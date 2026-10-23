<?php

namespace App\Actions\EnvironmentDomain;

use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\HostedDomainType;
use App\Models\Component;
use App\Models\Environment;
use App\Models\Ssl;

class GetEnvironmentDomainOptions
{
    /** @return array<string, mixed> */
    public function get(Environment $environment): array
    {
        $components = $environment->components()->with('server')->where('status', ComponentStatus::READY)
            ->where(fn ($query) => $query->where('type', ComponentType::LOAD_BALANCER)
                ->orWhereHas('capabilities', fn ($query) => $query->where('capability', 'web')->where('status', ComponentStatus::READY)))->get();

        return [
            'components' => $components->map(fn (Component $component): array => [
                'id' => $component->id, 'name' => $component->name, 'server_id' => $component->server_id,
                'server_name' => $component->server->name, 'server_ip' => $component->server->ip,
            ])->all(),
            'aliases' => $environment->domains()->where('type', HostedDomainType::ALIAS)->whereNotLike('host', '*.%')
                ->get(['id', 'host', 'component_id'])->toArray(),
            'certificates' => Ssl::activeServerLevel($components->pluck('server_id')->unique()->values()->all())
                ->where('expires_at', '>', now())->get()->filter(fn (Ssl $ssl): bool => is_array($ssl->domains) && $ssl->domains !== [])
                ->map(fn (Ssl $ssl): array => ['id' => $ssl->id, 'server_id' => $ssl->server_id, 'domains' => $ssl->domains])->values()->all(),
        ];
    }
}
