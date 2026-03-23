<?php

namespace App\Tables;

use App\Models\Backup;

class BackupTable extends AbstractTable
{
    protected string $pageName = 'backupsPage';

    protected ?string $realtimeEvent = 'backup';

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
            Column::data('_last_file_color', fn (Backup $backup) => $backup->lastFile?->status?->getColor()),
            Column::data('server_id'),
            Column::data('id'),
            Column::data('keep_backups'),
            Column::data('interval'),
            Column::data('database_id'),
            Column::data('path'),
            Column::data('storage_name', fn (Backup $backup) => $backup->storage->profile),
            Column::data('database_name', fn (Backup $backup) => $backup->database?->name),
        ];
    }
}
