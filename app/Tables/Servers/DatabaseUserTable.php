<?php

namespace App\Tables\Servers;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\ComponentColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class DatabaseUserTable extends Table
{
    protected bool $showHost = false;

    public function withHost(bool $showHost = true): static
    {
        $this->showHost = $showHost;

        return $this;
    }

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('username', 'Username')->sortable(),
            $this->showHost
                ? TextColumn::make('host', 'Host')->sortable()
                : Column::data('host'),
            EnumColumn::make('permission', 'Permission'),
            ComponentColumn::create('databases', 'Linked databases', 'DatabaseUserDatabases'),
            DateTimeColumn::make('created_at', 'Created at')->sortable()->toLocal(),
            EnumColumn::make('status', 'Status')->sortable(),
            Column::data('id'),
            Column::data('server_id'),
            ActionsColumn::make(),
        ];
    }
}
