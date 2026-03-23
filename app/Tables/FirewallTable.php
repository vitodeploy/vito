<?php

namespace App\Tables;

use App\Models\FirewallRule;

class FirewallTable extends AbstractTable
{
    protected string $pageName = 'firewallPage';

    protected ?string $realtimeEvent = 'firewall-rule';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Name')
                ->sortable(),
            Column::make('type', 'Type')
                ->sortable()
                ->value(fn (FirewallRule $rule) => strtoupper($rule->type))
                ->text(),
            Column::make('source', 'Source')
                ->sortable()
                ->value(fn (FirewallRule $rule) => $rule->source ?? 'any')
                ->text(),
            Column::make('protocol', 'Protocol')
                ->sortable()
                ->value(fn (FirewallRule $rule) => strtoupper($rule->protocol))
                ->text(),
            Column::make('port', 'Port')
                ->sortable(),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::data('server_id'),
            Column::data('id'),
            Column::data('note'),
            Column::data('mask'),
        ];
    }
}
