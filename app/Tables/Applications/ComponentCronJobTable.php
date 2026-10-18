<?php

namespace App\Tables\Applications;

use App\Http\Resources\CronJobResource;
use App\Models\CronJob;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BooleanColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ComponentCronJobTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('component.environment')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('command', 'Command'),
            TextColumn::make('frequency', 'Frequency'),
            BooleanColumn::make('managed', 'Managed')->value(fn (CronJob $cronJob): bool => $cronJob->component_capability_id !== null),
            EnumColumn::make('status', 'Status'),
            Column::data('cronjob', fn (CronJob $cronJob): array => (new CronJobResource($cronJob))->resolve()),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'command'];
    }
}
