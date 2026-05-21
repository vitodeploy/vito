<?php

namespace App\Tables;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class SshKeyTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->latest();
    }

    protected function columns(): array
    {
        return [
            Column::make('id', 'ID')->sortable(),
            TextColumn::make('name', 'Name')->sortable(),
            DateTimeColumn::make('created_at', 'Created at')->sortable()->toLocal(),
            ActionsColumn::make(),
        ];
    }
}
