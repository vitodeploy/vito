<?php

namespace App\Tables;

use App\Models\Backup;

class BackupTable extends AbstractTable
{
    protected string $pageName = 'backupsPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('type', 'Type')
                ->sortable()
                ->value(fn (Backup $backup) => $backup->type->value),
            Column::make('target', 'Target')
                ->value(fn (Backup $backup) => $backup->type->value === 'database' ? $backup->database?->name : $backup->path)
                ->copyable(),
            Column::make('storage_id', 'Storage')
                ->sortable()
                ->value(fn (Backup $backup) => $backup->storage->profile)
                ->text(),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::make('last_file', 'Last file status')
                ->value(fn (Backup $backup) => $backup->lastFile?->status?->getText())
                ->badge(
                    colorField: '_last_file_color',
                ),
            Column::make('_last_file_color', '')
                ->hidden()
                ->value(fn (Backup $backup) => $backup->lastFile?->status?->getColor()),
            Column::make('server_id', '')
                ->hidden(),
            Column::make('id', '')
                ->hidden(),
            Column::make('keep_backups', '')
                ->hidden(),
            Column::make('interval', '')
                ->hidden(),
            Column::make('database_id', '')
                ->hidden(),
            Column::make('path', '')
                ->hidden(),
            Column::make('storage_name', '')
                ->hidden()
                ->value(fn (Backup $backup) => $backup->storage->profile),
            Column::make('database_name', '')
                ->hidden()
                ->value(fn (Backup $backup) => $backup->database?->name),
        ];
    }
}
