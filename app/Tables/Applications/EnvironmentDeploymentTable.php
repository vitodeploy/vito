<?php

namespace App\Tables\Applications;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use App\Models\EnvironmentDeployment;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class EnvironmentDeploymentTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('triggeredBy', 'deployments.component')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('release', 'Release'),
            TextColumn::make('commit_id', 'Commit'),
            EnumColumn::make('status', 'Status'),
            EnumColumn::make('current_stage', 'Stage'),
            TextColumn::make('kind', 'Run')->value(fn (EnvironmentDeployment $run): string => $run->is_join
                ? 'Join: '.$run->deployments->pluck('component.name')->filter()->implode(', ')
                : ($run->rollback_of_id ? 'Rollback #'.$run->rollback_of_id : 'Deploy')),
            TextColumn::make('triggeredBy.name', 'Triggered by'),
            DateTimeColumn::make('created_at', 'Created')->sortable()->toLocal(),
            Column::data('id'),
            ActionsColumn::make(),
        ];
    }
}
