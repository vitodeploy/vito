<?php

namespace App\Tables;

use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BadgeColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class StorageProviderTable extends Table
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
            TextColumn::make('name', 'Name')
                ->value(fn ($m) => $m->profile)
                ->accessor('profile')
                ->sortable(),
            BadgeColumn::make('global_label', 'Global')
                ->value(fn ($m) => $m->project_id === null ? 'Yes' : 'No')
                ->colorField('global_color')
                ->accessor('project_id')
                ->sortable(),
            Column::data('global', fn ($m) => $m->project_id === null),
            Column::data('editable_data', fn ($m) => $m->editableDataFor(user())),
            Column::data('global_color', fn ($m) => $m->project_id === null ? 'success' : 'danger'),
            DateTimeColumn::make('created_at', 'Created at')->sortable()->toLocal(),
            ActionsColumn::make(),
        ];
    }
}
