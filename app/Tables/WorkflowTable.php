<?php

namespace App\Tables;

use App\Models\Workflow;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

/**
 * `run_inputs` is shipped as hidden row data so the Run modal can pre-fill the
 * JSON inputs editor without a second roundtrip.
 */
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
            Column::data('id'),
            Column::data('run_inputs', fn (Workflow $w) => $w->getStartingNode()->inputs ?? []),
            ActionsColumn::make(),
        ];
    }
}
