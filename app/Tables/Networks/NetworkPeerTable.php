<?php

namespace App\Tables\Networks;

use App\Models\NetworkPeer;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\CopyableColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class NetworkPeerTable extends Table
{
    protected array $tableSettings = ['realtime' => 'network-peer'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->orderBy('id');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('ip', 'IP address'),
            CopyableColumn::make('public_key', 'Public key'),
            EnumColumn::make('status', 'Status'),
            Column::make('last_handshake', 'Last handshake')
                ->value(fn (NetworkPeer $peer): string => $peer->last_handshake_at?->diffForHumans() ?? 'Never')
                ->badge(colorField: '_connected_color'),
            Column::data('_connected_color', fn (NetworkPeer $peer): string => $this->connected($peer) ? 'success' : 'gray'),
            Column::data('id'),
            Column::data('byo'),
            Column::data('has_private_key', fn (NetworkPeer $peer): bool => $peer->hasPrivateKey()),
            ActionsColumn::make(),
        ];
    }

    private function connected(NetworkPeer $peer): bool
    {
        return $peer->last_handshake_at !== null && $peer->last_handshake_at->greaterThan(now()->subMinutes(10));
    }
}
