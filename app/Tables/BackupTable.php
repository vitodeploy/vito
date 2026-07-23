<?php

namespace App\Tables;

use App\Enums\BackupType;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Models\Server;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BadgeColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Table;

class BackupTable extends Table
{
    protected array $tableSettings = ['realtime' => 'backup'];

    protected ?Server $server = null;

    public function forServer(Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with(['server', 'storage', 'database', 'lastFile'])->latest();
    }

    protected function columns(): array
    {
        $columns = [];

        if (! $this->server) {
            $columns[] = Column::make('server.name', 'Server')
                ->link('servers.show', ['server' => ':server_id']);
        }

        return [
            ...$columns,
            EnumColumn::make('type', 'Type')->sortable(),
            Column::make('target', 'Target')
                ->value(fn (Backup $backup) => $backup->type === BackupType::FILE ? $backup->path : $backup->database?->name)
                ->fallback('-'),
            Column::make('storage.profile', 'Storage'),
            BadgeColumn::make('last_file_status', 'Last file')
                ->value(fn (Backup $backup) => $backup->lastFile?->status->getText())
                ->colorField('last_file_status_color')
                ->fallback('-'),
            BadgeColumn::make('state', 'Status')
                ->value(fn (Backup $backup) => $backup->status?->getText() ?? ($backup->enabled ? 'enabled' : 'disabled'))
                ->colorField('state_color'),
            DateTimeColumn::make('created_at', 'Created at')->sortable(),
            Column::data('last_file_status_color', fn (Backup $backup) => $backup->lastFile?->status->getColor()),
            Column::data('state_color', fn (Backup $backup) => $backup->status?->getColor() ?? ($backup->enabled ? 'success' : 'gray')),
            Column::data('id'),
            Column::data('server_id'),
            Column::data('resource', fn (Backup $backup) => BackupResource::make($backup)),
            ActionsColumn::make(),
        ];
    }
}
