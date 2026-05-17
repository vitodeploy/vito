<?php

namespace App\Tables;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Table;

class WorkflowRunTable extends Table
{
    protected array $tableSettings = ['realtime' => 'workflow-run'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->latest();
    }

    protected function columns(): array
    {
        return [
            Column::make('id', 'ID')->sortable(),
            DateTimeColumn::make('created_at', 'Created at')->sortable()->toLocal(),
            EnumColumn::make('status', 'Status')->sortable(),
            // Used only by the frontend row-click to build the show route.
            // WorkflowRunController::show re-authorizes via Policy — do not
            // trust this value as an authorization boundary elsewhere.
            Column::data('workflow_id'),
        ];
    }
}
