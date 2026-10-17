<?php

namespace App\Tables;

use App\Models\Application;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ApplicationTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->withCount('environments')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('repository', 'Repository'),
            TextColumn::make('runtime', 'Runtime')
                ->value(fn (Application $application): string => config('component.runtimes', [])[$application->runtime]['label'] ?? $application->runtime),
            TextColumn::make('environments_count', 'Environments'),
            Column::data('id'),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'repository'];
    }
}
