<?php

namespace App\Tables;

use App\Models\Ssl;

class ServerSslTable extends AbstractTable
{
    protected string $pageName = 'sslsPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('id', 'ID')
                ->sortable(),
            Column::make('type', 'Type')
                ->sortable()
                ->badge(
                    fn (Ssl $ssl) => strtoupper($ssl->type ?? ''),
                    variant: 'default',
                ),
            Column::make('domains', 'Domain(s)')
                ->component('SslDomainsCell'),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('expires_at', 'Expires in')
                ->sortable()
                ->component('SslExpiresCell'),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::make('server_id', '')
                ->hidden()
                ->value(fn (Ssl $ssl) => $ssl->server_id ?? $ssl->site?->server_id),
            Column::make('site_id', '')
                ->hidden(),
            Column::make('is_wildcard', '')
                ->hidden(),
            Column::make('has_csr', '')
                ->hidden(),
            Column::make('csr_data', '')
                ->hidden()
                ->value(fn (Ssl $ssl) => $ssl->csr_data ? collect($ssl->csr_data)->only([
                    'common_name', 'organization', 'organizational_unit',
                    'city', 'state', 'country', 'email', 'key_size',
                ])->toArray() : null),
            Column::make('log', '')
                ->hidden()
                ->value(fn (Ssl $ssl) => $ssl->log_id && $ssl->log ? [
                    'id' => $ssl->log->id,
                    'server_id' => $ssl->log->server_id,
                    'site_id' => $ssl->log->site_id,
                    'type' => $ssl->log->type,
                    'name' => $ssl->log->name,
                    'disk' => $ssl->log->disk,
                    'is_remote' => $ssl->log->is_remote,
                    'created_at' => $ssl->log->created_at,
                    'updated_at' => $ssl->log->updated_at,
                ] : null),
        ];
    }
}
