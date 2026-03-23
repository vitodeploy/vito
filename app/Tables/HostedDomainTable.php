<?php

namespace App\Tables;

use App\Models\HostedDomain;

class HostedDomainTable extends AbstractTable
{
    protected string $pageName = 'hostedDomainsPage';

    protected ?string $realtimeEvent = 'hosted-domain';

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDir = 'asc';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('domain', 'Domain')
                ->sortable()
                ->component('HostedDomainCell'),
            Column::make('ssl', 'SSL')
                ->value(fn (HostedDomain $hd) => match ($hd->ssl_method->value) {
                    'none' => 'DISABLED',
                    'letsencrypt' => 'LETSENCRYPT',
                    default => 'CUSTOM',
                })
                ->badge(variant: 'outline'),
            Column::make('certificate', 'Certificate')
                ->component('HostedDomainCertificateCell'),
            Column::make('expires_at', 'Expires In')
                ->component('HostedDomainExpiresCell'),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::data('id'),
            Column::data('server_id', fn (HostedDomain $hd) => $hd->site->server_id),
            Column::data('site_id'),
            Column::data('type', fn (HostedDomain $hd) => $hd->type->getText()),
            Column::data('ssl_method', fn (HostedDomain $hd) => $hd->ssl_method->getText()),
            Column::data('ssl_id'),
            Column::data('ssl_type', fn (HostedDomain $hd) => $hd->ssl?->type),
            Column::data('ssl_domains', fn (HostedDomain $hd) => $hd->ssl?->domains),
            Column::data('ssl_expires_at', fn (HostedDomain $hd) => $hd->ssl?->expires_at),
            Column::data('error'),
        ];
    }
}
