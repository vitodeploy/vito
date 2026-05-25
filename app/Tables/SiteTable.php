<?php

namespace App\Tables;

use App\Models\Server;
use App\Models\Site;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BadgeColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class SiteTable extends Table
{
    protected array $tableSettings = ['realtime' => 'site'];

    protected ?Server $server = null;

    public function forServer(Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with(['server', 'isolatedUser', 'hostedDomains.ssl', 'workers'])->latest();
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
            Column::make('id', 'ID')->sortable(),
            TextColumn::make('domain', 'Domain')->sortable(),
            BadgeColumn::make('user', 'User')->variant('outline')->sortable(),
            BadgeColumn::make('type', 'Type')->variant('outline')->sortable(),
            DateTimeColumn::make('created_at', 'Created at')->sortable(),
            EnumColumn::make('status', 'Status')->sortable(),
            Column::data('server_id'),
            Column::data('warnings', fn (Site $site) => $site->getWarnings()),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['domain', 'user'];
    }
}
