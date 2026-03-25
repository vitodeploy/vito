<?php

namespace App\Tables;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\LinkColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ServerTable extends Table
{
    protected array $tableSettings = ['realtime' => 'server'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
    }

    protected function columns(): array
    {
        return [
            Column::make('id', 'ID')->sortable(),
            LinkColumn::make('name', 'Name')->sortable()->route('servers.show', ['server' => ':id']),
            TextColumn::make('ip', 'IP')->sortable(),
            DateTimeColumn::make('created_at', 'Created at')->sortable(),
            EnumColumn::make('status', 'Status')->sortable(),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'ip'];
    }
}
