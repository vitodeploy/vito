<?php

namespace App\Tables;

use App\Models\Server;
use App\Models\Site;

class SiteTable extends AbstractTable
{
    protected string $pageName = 'sitesPage';

    protected ?Server $server = null;

    public function forServer(?Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        $columns = [];

        if (! $this->server) {
            $columns[] = Column::make('server_name', 'Server')
                ->value(fn (Site $site) => $site->server->name)
                ->link('servers.show', ['server' => ':server_id']);
        }

        return [
            ...$columns,
            Column::make('id', 'ID')
                ->sortable(),
            Column::make('domain', 'Domain')
                ->sortable(),
            Column::make('type', 'Type')
                ->sortable()
                ->badge(variant: 'outline'),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::make('server_id', '')
                ->hidden(),
            Column::make('warnings', '')
                ->hidden()
                ->value(fn (Site $site) => $site->getWarnings()),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['domain'];
    }
}
