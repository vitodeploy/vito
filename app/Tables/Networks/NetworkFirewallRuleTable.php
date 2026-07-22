<?php

namespace App\Tables\Networks;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class NetworkFirewallRuleTable extends Table
{
    protected array $tableSettings = ['realtime' => 'network-firewall-rule'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->orderBy('id');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            Column::make('_protocol', 'Protocol')->accessor('protocol')->text()->fallback('*'),
            Column::make('_port', 'Port')->accessor('port')->text()->fallback('*'),
            EnumColumn::make('status', 'Status'),
            Column::data('id'),
            Column::data('protocol'),
            Column::data('port'),
            ActionsColumn::make(),
        ];
    }
}
