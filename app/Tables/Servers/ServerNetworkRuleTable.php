<?php

namespace App\Tables\Servers;

use App\Enums\NetworkServerStatus;
use App\Models\ServerNetworkRule;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\LinkColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ServerNetworkRuleTable extends Table
{
    protected array $tableSettings = ['realtime' => 'server-network-rule'];

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query
            ->whereHas('networkServer', fn ($query) => $query->where('status', '!=', NetworkServerStatus::LEAVING))
            ->with('network');

        ServerNetworkRule::applyOrder($this->query);
    }

    protected function columns(): array
    {
        return [
            LinkColumn::make('network.name', 'Network')->route('networks.show', ['network' => ':network_id']),
            TextColumn::make('name', 'Name'),
            TextColumn::make('type', 'Type')->uppercase(),
            Column::make('source', 'Source')
                ->value(fn (ServerNetworkRule $rule): string => $this->sourceLabel($rule))
                ->text(),
            TextColumn::make('protocol', 'Protocol')->fallback('*'),
            TextColumn::make('port', 'Port')->fallback('*'),
            EnumColumn::make('status', 'Status'),
            Column::data('network_id'),
        ];
    }

    private function sourceLabel(ServerNetworkRule $rule): string
    {
        if ($rule->source === null) {
            return '*';
        }

        return $rule->mask !== null && $rule->mask !== 32 ? $rule->source.'/'.$rule->mask : $rule->source;
    }
}
