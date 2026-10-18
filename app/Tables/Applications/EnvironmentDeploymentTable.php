<?php

namespace App\Tables\Applications;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BooleanColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class EnvironmentDeploymentTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('triggeredBy')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('release', 'Release'),
            TextColumn::make('commit_id', 'Commit'),
            EnumColumn::make('status', 'Status'),
            EnumColumn::make('current_stage', 'Stage'),
            BooleanColumn::make('is_join', 'Join'),
            TextColumn::make('triggeredBy.name', 'Triggered by'),
            DateTimeColumn::make('created_at', 'Created')->sortable()->toLocal(),
            Column::data('id'),
            ActionsColumn::make(),
        ];
    }
}
