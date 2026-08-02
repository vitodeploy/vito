<?php

namespace App\Tables;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BadgeColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class DnsProviderTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->latest();
    }

    protected function columns(): array
    {
        return [
            Column::make('id', 'ID')->sortable(),
            TextColumn::make('provider', 'Provider')->sortable(),
            TextColumn::make('name', 'Name')->sortable(),
            BadgeColumn::make('scope_label', 'Scope')
                ->variant('outline')
                ->value(fn ($m) => $m->project_id === null ? 'global' : 'project')
                ->accessor('project_id')
                ->sortable(),
            Column::data('global', fn ($m) => $m->project_id === null),
            Column::data('connected'),
            Column::data('editable_data', fn ($m) => $m->editableDataFor(user())),
            DateTimeColumn::make('created_at', 'Created at')->sortable()->toLocal(),
            ActionsColumn::make(),
        ];
    }
}
