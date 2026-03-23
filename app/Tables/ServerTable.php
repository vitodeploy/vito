<?php

namespace App\Tables;

class ServerTable extends AbstractTable
{
    protected string $pageName = 'serversPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('id', 'ID')
                ->sortable(),
            Column::make('name', 'Name')
                ->sortable()
                ->link('servers.show', ['server' => ':id']),
            Column::make('ip', 'IP')
                ->sortable(),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name', 'ip'];
    }
}
