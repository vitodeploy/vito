<?php

namespace App\Tables;

class DatabaseTable extends AbstractTable
{
    protected string $pageName = 'databasesPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Name')
                ->sortable()
                ->icon(fn () => 'database')
                ->text(),
            Column::make('charset', 'Charset')
                ->sortable(),
            Column::make('collation', 'Collation')
                ->sortable(),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::make('server_id', '')
                ->hidden(),
        ];
    }
}
