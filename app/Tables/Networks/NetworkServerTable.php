<?php

namespace App\Tables\Networks;

use App\Models\NetworkServer;
use App\Models\Service;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\LinkColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class NetworkServerTable extends Table
{
    protected array $tableSettings = ['realtime' => 'network-server'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with(['server.services', 'serverIpAddress'])->orderBy('id');
    }

    protected function columns(): array
    {
        return [
            LinkColumn::make('server.name', 'Server')->sortable()->route('servers.show', ['server' => ':server_id']),
            TextColumn::make('ip', 'IP address')
                ->value(fn (NetworkServer $member) => $member->ip ?? $member->serverIpAddress?->ip)
                ->fallback('-'),
            Column::make('firewall', 'Firewall')
                ->value(fn (NetworkServer $member): string => $this->hasFirewall($member) ? 'Yes' : 'No')
                ->badge(colorField: '_firewall_color'),
            Column::data('_firewall_color', fn (NetworkServer $member): string => $this->hasFirewall($member) ? 'success' : 'danger'),
            EnumColumn::make('status', 'Status'),
            Column::data('id'),
            Column::data('server_id'),
            ActionsColumn::make(),
        ];
    }

    private function hasFirewall(NetworkServer $member): bool
    {
        return $member->server->services->contains(fn (Service $service): bool => $service->type === 'firewall');
    }
}
