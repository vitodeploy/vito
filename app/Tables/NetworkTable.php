<?php

namespace App\Tables;

use App\Models\Network;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class NetworkTable extends Table
{
    protected array $tableSettings = ['realtime' => 'network'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('serverProvider')->withCount('servers')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            EnumColumn::make('type', 'Type'),
            Column::make('provider', 'Provider')
                ->value(fn (Network $network): string => $network->server_provider_id !== null
                    ? $network->serverProvider->provider
                    : '-'),
            TextColumn::make('cidr', 'CIDR'),
            TextColumn::make('port', 'Port')->fallback('-'),
            TextColumn::make('servers_count', 'Servers'),
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
