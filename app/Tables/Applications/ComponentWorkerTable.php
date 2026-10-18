<?php

namespace App\Tables\Applications;

use App\Http\Resources\WorkerResource;
use App\Models\Worker;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BooleanColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ComponentWorkerTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('component.environment', 'site')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('command', 'Command'),
            TextColumn::make('numprocs', 'Processes'),
            BooleanColumn::make('managed', 'Managed')->value(fn (Worker $worker): bool => $worker->component_capability_id !== null),
            EnumColumn::make('status', 'Status'),
            Column::data('worker', fn (Worker $worker): array => (new WorkerResource($worker))->resolve()),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'command'];
    }
}
