<?php

namespace App\Tables\Applications;

use App\Models\EnvironmentDeployment;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
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
            TextColumn::make('number', 'Run')->value(fn (EnvironmentDeployment $run): string => '#'.$run->id),
            TextColumn::make('commit', 'Commit')->value(fn (EnvironmentDeployment $run): ?string => $run->commit_id ? substr($run->commit_id, 0, 7) : null)->fallback('Resolving'),
            EnumColumn::make('status', 'Status'),
            EnumColumn::make('current_stage', 'Stage'),
            TextColumn::make('kind', 'Type')->value(fn (EnvironmentDeployment $run): string => $run->is_join
                ? 'Join: '.$run->deployments->pluck('component.name')->filter()->implode(', ')
                : ($run->rollback_of_id ? 'Rollback #'.$run->rollback_of_id : 'Deploy')),
            TextColumn::make('triggeredBy.name', 'Triggered by'),
            DateTimeColumn::make('created_at', 'Created')->sortable()->toLocal(),
            Column::data('id'),
            Column::data('commit_id'),
            Column::data('release'),
            ActionsColumn::make(),
        ];
    }
}
