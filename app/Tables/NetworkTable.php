<?php

namespace App\Tables;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BooleanColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class NetworkTable extends Table
{
    protected array $tableSettings = ['realtime' => 'network'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->withCount('servers')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            EnumColumn::make('type', 'Type'),
            TextColumn::make('servers_count', 'Servers'),
            BooleanColumn::make('firewall_enabled', 'Firewall'),
            EnumColumn::make('status', 'Status')->sortable(),
            Column::data('id'),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['name'];
    }
}
