<?php

namespace App\Tables;

class DatabaseUserTable extends AbstractTable
{
    protected string $pageName = 'databaseUsersPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('username', 'Username')
                ->sortable(),
            Column::make('permission', 'Permission')
                ->sortable()
                ->badge(variant: 'outline'),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::data('server_id'),
            Column::data('id'),
            Column::data('databases'),
            Column::data('host'),
        ];
    }
}
