<?php

namespace App\Tables\Applications;

use App\Models\EnvironmentDeployment;
use Forjed\InertiaTable\Column;
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
            EnumColumn::make('status', 'Status'),
            TextColumn::make('message', 'Commit')->value(fn (EnvironmentDeployment $run): string => $run->is_join
                ? 'Joined '.$run->deployments->pluck('component.name')->filter()->implode(', ')
                : ($run->rollback_of_id ? 'Rollback to #'.$run->rollback_of_id : (str($run->commit_data['message'] ?? '')->before("\n")->limit(80)->value() ?: 'Deploy '.$run->branch))),
            TextColumn::make('commit', 'SHA')->value(fn (EnvironmentDeployment $run): ?string => $run->commit_id ? substr($run->commit_id, 0, 7) : null)->fallback('—'),
            TextColumn::make('triggered_by', 'By')->value(fn (EnvironmentDeployment $run): string => $run->triggeredBy->name ?? ($run->is_join ? 'Vito' : 'Git push')),
            DateTimeColumn::make('created_at', 'Created')->sortable()->toLocal(),
            Column::data('id'),
            Column::data('commit_id'),
            Column::data('release'),
        ];
    }
}
