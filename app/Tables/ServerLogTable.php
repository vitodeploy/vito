<?php

namespace App\Tables;

class ServerLogTable extends AbstractTable
{
    protected string $pageName = 'logsPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Event')
                ->sortable(),
            Column::make('created_at', 'Created At')
                ->sortable()
                ->date(),
            Column::make('id', '')
                ->hidden(),
            Column::make('server_id', '')
                ->hidden(),
            Column::make('site_id', '')
                ->hidden(),
            Column::make('type', '')
                ->hidden(),
            Column::make('disk', '')
                ->hidden(),
            Column::make('is_remote', '')
                ->hidden(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name'];
    }
}
