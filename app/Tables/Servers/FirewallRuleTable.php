<?php

namespace App\Tables\Servers;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class FirewallRuleTable extends Table
{
    protected array $tableSettings = ['realtime' => 'firewall-rule'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('type', 'Type')->sortable(),
            TextColumn::make('source', 'Source')->fallback('any')->sortable(),
            TextColumn::make('protocol', 'Protocol')->sortable(),
            TextColumn::make('port', 'Port')->sortable(),
            EnumColumn::make('status', 'Status')->sortable(),
            Column::data('id'),
            Column::data('server_id'),
            Column::data('mask'),
            ActionsColumn::make(),
        ];
    }
}
