<?php

namespace App\Tables;

class ServerLogTable extends AbstractTable
{
    protected string $pageName = 'logsPage';

    protected ?string $realtimeEvent = 'server-log';

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
            Column::data('id'),
            Column::data('server_id'),
            Column::data('site_id'),
            Column::data('type'),
            Column::data('disk'),
            Column::data('is_remote'),
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
