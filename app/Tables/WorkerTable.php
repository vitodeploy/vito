<?php

namespace App\Tables;

use App\Models\Worker;

class WorkerTable extends AbstractTable
{
    protected string $pageName = 'workersPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Name')
                ->sortable(),
            Column::make('command', 'Command')
                ->sortable()
                ->copyable(),
            Column::make('user', 'User')
                ->sortable(),
            Column::make('site_id', 'Site')
                ->sortable()
                ->value(fn (Worker $worker) => $worker->site->domain ?? '-')
                ->text(),
            Column::make('numprocs', 'Numprocs')
                ->sortable(),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::make('server_id', '')
                ->hidden(),
            Column::make('id', '')
                ->hidden(),
            Column::make('auto_start', '')
                ->hidden(),
            Column::make('auto_restart', '')
                ->hidden(),
        ];
    }
}
