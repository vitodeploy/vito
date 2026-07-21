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
        $this->query->orderBy('position')->orderBy('id');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('type', 'Type'),
            TextColumn::make('protocol', 'Protocol'),
            TextColumn::make('port', 'Port'),
            TextColumn::make('position', 'Position')->sortable(),
            EnumColumn::make('status', 'Status'),
            Column::data('id'),
            ActionsColumn::make(),
        ];
    }
}
