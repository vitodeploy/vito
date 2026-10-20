<?php

namespace App\Tables\Applications;

use App\Http\Resources\EnvironmentDomainResource;
use App\Models\EnvironmentDomain;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BooleanColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class EnvironmentDomainTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('component.server', 'redirectToDomain')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('host', 'Host')->sortable(),
            TextColumn::make('target', 'Target')->value(fn (EnvironmentDomain $domain): string => $domain->redirectToDomain
                ? '→ '.$domain->redirectToDomain->host
                : $domain->component->name.' · '.$domain->component->server->name),
            EnumColumn::make('type', 'Type'),
            EnumColumn::make('ssl_method', 'SSL'),
            BooleanColumn::make('force_ssl', 'Force SSL'),
            EnumColumn::make('status', 'Status'),
            TextColumn::make('error', 'Error'),
            Column::data('id'),
            Column::data('domain', fn (EnvironmentDomain $domain): array => (new EnvironmentDomainResource($domain))->resolve()),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['host'];
    }
}
