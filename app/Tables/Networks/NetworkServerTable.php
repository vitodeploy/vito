<?php

namespace App\Tables\Networks;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class NetworkServerTable extends Table
{
    protected array $tableSettings = ['realtime' => 'network-server'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('server', 'serverIpAddress')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('server.name', 'Server')->sortable(),
            TextColumn::make('ip', 'Tunnel IP')->fallback('—'),
            EnumColumn::make('status', 'Status'),
            Column::data('id'),
            Column::data('server_id'),
            ActionsColumn::make(),
        ];
    }
}
