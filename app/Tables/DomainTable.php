<?php

namespace App\Tables;

class DomainTable extends AbstractTable
{
    protected string $pageName = 'domainsPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('domain', 'Domain')
                ->sortable(),
            Column::make('dns_provider_name', 'DNS Provider')
                ->value(fn ($m) => $m->dnsProvider?->name)
                ->badge(variant: 'outline'),
            Column::make('created_at', 'Added at')
                ->sortable()
                ->date(),
            Column::make('id', '')
                ->hidden(),
            Column::make('dns_provider_id', '')
                ->hidden(),
            Column::make('metadata', '')
                ->hidden(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['domain'];
    }
}
