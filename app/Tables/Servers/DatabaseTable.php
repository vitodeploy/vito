<?php

namespace App\Tables\Servers;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class DatabaseTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->withIcon('database')->sortable(),
            TextColumn::make('charset', 'Charset')->sortable(),
            TextColumn::make('collation', 'Collation')->sortable(),
            DateTimeColumn::make('created_at', 'Created at')->sortable()->toLocal(),
            EnumColumn::make('status', 'Status')->sortable(),
            Column::data('id'),
            Column::data('server_id'),
            ActionsColumn::make(),
        ];
    }
}
