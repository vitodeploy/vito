<?php

namespace App\Tables;

use App\Models\HostedDomain;

class HostedDomainTable extends AbstractTable
{
    protected string $pageName = 'hostedDomainsPage';

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
            Column::make('id', '')
                ->hidden(),
            Column::make('server_id', '')
                ->hidden()
                ->value(fn (HostedDomain $hd) => $hd->site->server_id),
            Column::make('site_id', '')
                ->hidden(),
            Column::make('type', '')
                ->hidden()
                ->value(fn (HostedDomain $hd) => $hd->type->getText()),
            Column::make('ssl_method', '')
                ->hidden()
                ->value(fn (HostedDomain $hd) => $hd->ssl_method->getText()),
            Column::make('ssl_id', '')
                ->hidden(),
            Column::make('ssl_type', '')
                ->hidden()
                ->value(fn (HostedDomain $hd) => $hd->ssl?->type),
            Column::make('ssl_domains', '')
                ->hidden()
                ->value(fn (HostedDomain $hd) => $hd->ssl?->domains),
            Column::make('ssl_expires_at', '')
                ->hidden()
                ->value(fn (HostedDomain $hd) => $hd->ssl?->expires_at),
            Column::make('error', '')
                ->hidden(),
        ];
    }
}
