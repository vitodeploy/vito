<?php

namespace App\Tables;

use App\Models\CronJob;

class CronJobTable extends AbstractTable
{
    protected string $pageName = 'cronjobsPage';

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
                ->value(fn (CronJob $cronJob) => $cronJob->site->domain ?? '-')
                ->text(),
            Column::make('frequency', 'Frequency')
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
        ];
    }
}
