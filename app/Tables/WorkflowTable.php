<?php

namespace App\Tables;

use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class WorkflowTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            DateTimeColumn::make('created_at', 'Created at')->sortable()->toLocal(),
            DateTimeColumn::make('updated_at', 'Updated at')->sortable()->toLocal(),
            ActionsColumn::make(),
        ];
    }
}
