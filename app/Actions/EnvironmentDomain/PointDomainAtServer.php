<?php

namespace App\Actions\EnvironmentDomain;

use App\Actions\Domain\CreateDNSRecord;
use App\Models\Domain;
use App\Models\Environment;
use App\Models\EnvironmentDomain;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Throwable;

class PointDomainAtServer
{
    /**
     * Project domains whose DNS Vito can manage for this environment.
     *
     * @return Builder<Domain>
     */
    public function available(Environment $environment): Builder
    {
        $projectId = $environment->application->project_id;

        return Domain::query()->where('project_id', $projectId)
            ->whereHas('dnsProvider', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('project_id', $projectId)->orWhereNull('project_id')));
    }

    /**
     * Creates the address record through the DNS provider. An existing record for the host is never
     * overwritten: it may be serving traffic, so changing it stays an explicit decision.
     *
     * @throws ValidationException
     */
    public function point(EnvironmentDomain $domain, int $dnsDomainId): void
    {
        $dnsDomain = $this->available($domain->environment)->find($dnsDomainId)
            ?? throw ValidationException::withMessages(['dns_domain_id' => 'Select a domain managed in this project.']);
        if ($domain->host !== $dnsDomain->domain && ! str_ends_with($domain->host, '.'.$dnsDomain->domain)) {
            throw ValidationException::withMessages(['host' => "The host must be {$dnsDomain->domain} or a subdomain of it."]);
        }

        $ip = $domain->component->server->ip;
        $type = str_contains($ip, ':') ? 'AAAA' : 'A';
        $relative = $domain->host === $dnsDomain->domain ? '@' : substr($domain->host, 0, -strlen($dnsDomain->domain) - 1);

        try {
            $dnsDomain->syncDnsRecords();
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['host' => 'Could not read the DNS records for '.$dnsDomain->domain.'. Check the DNS provider connection and retry.']);
        }

        $existing = $dnsDomain->records()->whereIn('name', [$domain->host, $relative])->whereIn('type', ['A', 'AAAA', 'CNAME'])->get();
        $domain->dns_domain_id = $dnsDomain->id;
        if ($existing->contains(fn ($record): bool => $record->type === $type && $record->content === $ip)) {
            return;
        }
        if ($conflict = $existing->first()) {
            throw ValidationException::withMessages(['host' => "{$domain->host} already has a {$conflict->type} record pointing to {$conflict->content}. Change it on the Domains page, or add this host as another domain and manage DNS yourself."]);
        }

        try {
            app(CreateDNSRecord::class)->create($dnsDomain, ['type' => $type, 'name' => $domain->host, 'content' => $ip, 'ttl' => 300, 'proxied' => false]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['host' => $e->validator->errors()->first()]);
        }
    }
}
