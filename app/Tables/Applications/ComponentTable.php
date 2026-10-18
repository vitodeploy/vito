<?php

namespace App\Tables\Applications;

use App\Models\Component;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ComponentTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('server', 'capabilities')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('server.name', 'Server'),
            TextColumn::make('user', 'User'),
            EnumColumn::make('type', 'Type'),
            TextColumn::make('capabilities', 'Capabilities')->value(fn (Component $component): string => $component->capabilities->pluck('capability')->implode(', ')),
            EnumColumn::make('status', 'Status'),
            Column::data('id'),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'user'];
    }
}
