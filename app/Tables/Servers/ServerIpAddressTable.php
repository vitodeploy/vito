<?php

namespace App\Tables\Servers;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ServerIpAddressTable extends Table
{
    protected array $tableSettings = ['realtime' => 'server-ip'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->orderByDesc('is_primary')->orderBy('ip');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('ip', 'IP Address')->sortable(),
            EnumColumn::make('family', 'Family')->sortable(),
            TextColumn::make('interface', 'Interface')->fallback('-')->sortable(),
            EnumColumn::make('type', 'Type')->sortable(),
            EnumColumn::make('status', 'Status')->sortable(),
            Column::data('id'),
            Column::data('server_id'),
            Column::data('is_managed'),
            Column::data('is_primary'),
            ActionsColumn::make(),
        ];
    }
}
