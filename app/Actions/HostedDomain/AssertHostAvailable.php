<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainType;
use App\Models\HostedDomain;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Validation\ValidationException;

class AssertHostAvailable
{
    public function assert(Server $server, string $host, string $field, ?HostedDomain $ignore = null): void
    {
        $siteExists = Site::query()
            ->where('server_id', $server->id)
            ->where('domain', $host)
            ->when($ignore?->type === HostedDomainType::PRIMARY, fn ($query) => $query->whereKeyNot($ignore->site_id))
            ->exists();

        $hostedDomainExists = HostedDomain::query()
            ->where('domain', $host)
            ->whereHas('site', fn ($query) => $query->where('server_id', $server->id))
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();

        if ($siteExists || $hostedDomainExists) {
            throw ValidationException::withMessages([
                $field => 'This domain is already in use on this server.',
            ]);
        }
    }
}
